<script src="https://sandbox.fluidpay.com/tokenizer/tokenizer.js"></script>
<script>
    window.AkauntingFluidPay = window.AkauntingFluidPay || (function () {
        const instances = new Map();
        const messages = @json([
            'tokenization_failed' => __('Tokenization failed. Please verify your information and try again.'),
            'processing_error' => __('Unable to process the payment. Please try again or contact support.'),
            'network_error' => __('Network error while contacting FluidPay. Please try again.')
        ]);

        const MAX_TOKENIZER_WAIT = 20;

        function waitForTokenizer(callback, attempt = 0) {
            if (typeof window.Tokenizer !== 'undefined') {
                callback();
                return;
            }

            if (attempt >= MAX_TOKENIZER_WAIT) {
                console.warn('FluidPay Tokenizer script not loaded.');
                return;
            }

            setTimeout(function () {
                waitForTokenizer(callback, attempt + 1);
            }, 150);
        }

        function instantiateTokenizer(options, container) {
            if (instances.has(options.containerId)) {
                const existing = instances.get(options.containerId);

                if (existing.instance && typeof existing.instance.destroy === 'function') {
                    existing.instance.destroy();
                }

                container.innerHTML = '';
            }

            const config = {
                apikey: options.publicKey,
                container: '#' + options.containerId,
                submission: function (response) {
                    if (response.status !== 'success') {
                        alert(response.msg || messages.tokenization_failed);
                        return;
                    }

                    submitToken(options, response.token);
                },
            };

            if (options.url) {
                config.url = options.url;
            }

            if (options.amount) {
                config.amount = options.amount;
            }

            if (options.currency) {
                config.currency = options.currency;
            }

            if (options.settings) {
                config.settings = options.settings;
            }

            const instance = new window.Tokenizer(config);

            instances.set(options.containerId, {
                instance: instance,
                options: options,
            });

            updateButtonLabels(options.containerId, options.submitLabel);
        }

        function init(options) {
            init.queue.push(options);
            flushQueue();
        }

        init.queue = [];

        function flushQueue() {
            if (init.queue.length === 0) {
                return;
            }

            waitForTokenizer(function () {
                while (init.queue.length) {
                    const options = init.queue.shift();

                    if (!options || !options.containerId) {
                        continue;
                    }

                    const container = document.getElementById(options.containerId);

                    if (!container) {
                        console.warn('FluidPay container not found:', options.containerId);
                        continue;
                    }

                    instantiateTokenizer(options, container);
                }
            });
        }

        function updateButtonLabels(containerId, label) {
            document.querySelectorAll('[data-fluidpay-submit="' + containerId + '"]').forEach(function (button) {
                if (label) {
                    button.textContent = label;
                } else if (button.dataset.fluidpaySubmitLabel) {
                    button.textContent = button.dataset.fluidpaySubmitLabel;
                }
            });
        }

        function processConfigElement(element) {
            if (!element || element.dataset.fluidpayProcessed) {
                return;
            }

            const raw = element.getAttribute('data-fluidpay-config');

            if (!raw) {
                return;
            }

            let options;

            try {
                options = JSON.parse(raw);
            } catch (error) {
                console.warn('Invalid FluidPay configuration JSON:', error);
                return;
            }

            element.dataset.fluidpayProcessed = '1';

            init(options);
        }

        function bootstrapConfigElements(root) {
            (root || document).querySelectorAll('[data-fluidpay-config]').forEach(function (element) {
                processConfigElement(element);
            });
        }

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== Node.ELEMENT_NODE) {
                        return;
                    }

                    if (node.hasAttribute && node.hasAttribute('data-fluidpay-config')) {
                        processConfigElement(node);
                        return;
                    }

                    bootstrapConfigElements(node);
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });

        bootstrapConfigElements();

        if (window.axios && !window.axios.__fluidpayRequestInterceptor) {
            window.axios.interceptors.request.use(function (config) {
                if (config && config.params && config.params.payment_method === 'fluidpay.tokenizer') {
                    delete config.params.payment_method;
                }

                return config;
            });

            window.axios.__fluidpayRequestInterceptor = true;
        }

        function submitToken(options, token) {
            if (!options.tokenEndpoint) {
                console.warn('FluidPay token endpoint missing.');
                return;
            }

            const triggers = document.querySelectorAll('[data-fluidpay-submit="' + options.containerId + '"]');
            const loadingText = triggers[0]?.getAttribute('data-fluidpay-submit-loading') || 'Processing...';

            triggers.forEach(function (button) {
                button.dataset.originalText = button.textContent;
                button.textContent = loadingText;
                button.disabled = true;
            });

            fetch(options.tokenEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': options.csrfToken || '',
                },
                body: JSON.stringify({
                    token: token,
                    invoice_number: options.invoiceNumber,
                    amount: options.amount,
                }),
            })
                .then(function (response) {
                    return response.json().catch(function () {
                        return {};
                    });
                })
                .then(function (payload) {
                    if (payload.success) {
                        window.location.reload();
                        return;
                    }

                    alert(payload.error || messages.processing_error);
                })
                .catch(function () {
                    alert(messages.network_error);
                })
                .finally(function () {
                    triggers.forEach(function (button) {
                        button.textContent = button.dataset.originalText || '';
                        button.disabled = false;
                    });
                });
        }

        function submit(containerId) {
            const entry = instances.get(containerId);

            if (!entry) {
                console.warn('FluidPay tokenizer not initialised for container:', containerId);
                return;
            }

            entry.instance.submit(entry.options.submitAmount || undefined);
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-fluidpay-submit]');

            if (!trigger) {
                return;
            }

            event.preventDefault();

            submit(trigger.getAttribute('data-fluidpay-submit'));
        });

        flushQueue();

        return {
            init: init,
            submit: submit,
        };
    })();
</script>
