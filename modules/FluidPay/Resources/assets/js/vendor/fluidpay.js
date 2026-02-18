window.AkauntingFluidPay = window.AkauntingFluidPay || (function () {
    const baseConfig = window.AkauntingFluidPayConfig || {};
    const instances = new Map();
    const fluidpayOrigin = baseConfig.origin || '';
    const messages = baseConfig.messages || {};

    const MAX_TOKENIZER_WAIT = baseConfig.maxTokenizerWait || 20;

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
        logDebug('Initializing tokenizer.', options);

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
                    alert(response.msg || messages.tokenization_failed || 'Tokenization failed.');
                    return;
                }

                submitToken(options, response.token);
            },
            validCard: function (card) {
                logDebug('Tokenizer valid card payload', card);
                updateDisclosure(options, card);
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

        let instance = null;

        try {
            instance = new window.Tokenizer(config);
        } catch (error) {
            console.error('[FluidPay] Tokenizer init failed', error);
            return;
        }

        instances.set(options.containerId, {
            instance: instance,
            options: options,
        });

        updateButtonLabels(options.containerId, options.submitLabel);

        setTimeout(function () {
            const containerSelector = '#' + options.containerId;
            const iframe = document.querySelector(containerSelector + ' iframe');
            const iframeCount = iframe ? 1 : 0;
            logDebug('Tokenizer iframe count', { containerId: options.containerId, iframeCount: iframeCount });

            if (!iframe) {
                console.warn('[FluidPay] Tokenizer did not render iframe. Check config:', options);
                return;
            }

            iframe.style.width = '100%';
            iframe.style.minHeight = '280px';
            iframe.style.border = '0';
        }, 500);
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
                    logDebug('Container not found for config.', options.containerId);
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

    function updateDisclosure(options, card) {
        const containerId = options?.containerId;
        const disclosureElement = document.querySelector('[data-fluidpay-disclosure-for="' + containerId + '"]');

        if (!disclosureElement) {
            return;
        }

        const calculateFees = !!options?.settings?.payment?.calculateFees;
        const disclosure = card?.Disclosure || card?.disclosure || card?.disclosure_text || '';
        const fees = card?.fees || {};
        const serviceFeeValue = fees?.service_fee ?? fees?.serviceFee ?? card?.ServiceFee ?? card?.service_fee ?? card?.serviceFee;
        const surchargeValue = fees?.surcharge ?? fees?.Surcharge ?? card?.Surcharge ?? card?.surcharge;
        const requestedAmountValue = fees?.requested_amount ?? fees?.requestedAmount;

        if (!calculateFees) {
            disclosureElement.classList.add('hidden');
            disclosureElement.textContent = '';
            return;
        }

        const lines = [];
        const defaultDisclosure = messages.default_disclosure
            || 'Service fees or surcharges may apply. The final amount will be shown before you submit your payment.';

        lines.push(disclosure || defaultDisclosure);

        const serviceFeeLabel = formatFeeAmount(serviceFeeValue, options?.currency);
        const surchargeLabel = formatFeeAmount(surchargeValue, options?.currency);
        const totalLabel = formatFeeAmount(requestedAmountValue, options?.currency);
        const serviceFeeNumeric = Number(serviceFeeValue);

        if (serviceFeeLabel && (!Number.isFinite(serviceFeeNumeric) || serviceFeeNumeric > 0)) {
            lines.push('Service Fee: ' + serviceFeeLabel);
        }

        if (surchargeLabel) {
            lines.push('Surcharge: ' + surchargeLabel);
        }

        if (totalLabel) {
            lines.push('Total with fees: ' + totalLabel);
        }

        disclosureElement.textContent = lines.join(' ');
        disclosureElement.classList.remove('hidden');
    }

    function formatFeeAmount(value, currency) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return '';
        }

        const numeric = Number(value);
        if (Number.isNaN(numeric)) {
            return String(value);
        }

        const amount = numeric / 100;

        if (typeof Intl !== 'undefined' && currency) {
            try {
                return new Intl.NumberFormat(undefined, {
                    style: 'currency',
                    currency: currency,
                }).format(amount);
            } catch (error) {
                return amount.toFixed(2);
            }
        }

        return amount.toFixed(2);
    }

    function isHidden(element) {
        if (!element) {
            return true;
        }

        return element.offsetParent === null || element.getClientRects().length === 0;
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

        if (options && options.containerId) {
            const container = element.previousElementSibling;

            if (!container || !container.id) {
                logDebug('Container not found for config.', options.containerId);
                return;
            }

            const matches = document.querySelectorAll('#' + options.containerId).length;

            if (matches > 1 || container.id !== options.containerId) {
                const uniqueId = options.containerId + '-' + Math.random().toString(36).slice(2, 8);
                const wrapper = element.closest('.space-y-4');

                container.id = uniqueId;
                options.containerId = uniqueId;

                if (wrapper) {
                    wrapper.querySelectorAll('[data-fluidpay-submit]').forEach(function (button) {
                        button.dataset.fluidpaySubmit = uniqueId;
                    });

                    const saveCheckbox = wrapper.querySelector('[data-fluidpay-save-for]');
                    if (saveCheckbox) {
                        saveCheckbox.dataset.fluidpaySaveFor = uniqueId;
                        saveCheckbox.id = uniqueId + '-save';
                    }

                    const saveLabel = wrapper.querySelector('label[for]');
                    if (saveLabel) {
                        saveLabel.setAttribute('for', uniqueId + '-save');
                    }
                }
            }

            if (isHidden(container)) {
                logDebug('Container appears hidden, attempting init anyway.', options.containerId);
            }
        }

        element.dataset.fluidpayProcessed = '1';

        init(options);
    }

    function bootstrapConfigElements(root) {
        (root || document).querySelectorAll('[data-fluidpay-config]').forEach(function (element) {
            processConfigElement(element);
        });
    }

    function logDebug(message, data) {
        if (!window.AkauntingFluidPayDebug) {
            return;
        }

        if (typeof data === 'undefined') {
            console.log('[FluidPay]', message);
        } else {
            console.log('[FluidPay]', message, data);
        }
    }

    function reload() {
        logDebug('Reloading tokenizer configs...');
        bootstrapConfigElements();
    }

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }

                if (node.hasAttribute && node.hasAttribute('data-fluidpay-config')) {
                    logDebug('Found config element (direct).', node);
                    processConfigElement(node);
                    return;
                }

                bootstrapConfigElements(node);
            });
        });
    });

    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    window.addEventListener('message', function (event) {
        if (!window.AkauntingFluidPayDebug) {
            return;
        }

        if (fluidpayOrigin && event.origin && event.origin.indexOf(fluidpayOrigin) !== 0) {
            return;
        }

        logDebug('Tokenizer message', {
            origin: event.origin,
            data: event.data,
        });
    });

    bootstrapConfigElements();

    document.addEventListener('click', function (event) {
        if (!event || !event.target || !event.target.id) {
            return;
        }

        if (event.target.id.indexOf('tabs-payment-method-fluidpay') !== -1) {
            setTimeout(function () {
                reload();
            }, 150);
        }
    });

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

        window.axios.post(options.tokenEndpoint, {
            token: token,
            invoice_number: options.invoiceNumber,
            amount: options.submitAmount,
            save_payment_method: getSaveCardChoice(options.containerId),
        }, {
            headers: {
                'X-CSRF-TOKEN': options.csrfToken,
            },
        }).then(function (response) {
            if (response.data && response.data.success) {
                window.location.reload();
            } else {
                const errorMessage = response.data?.error || messages.processing_error || 'Unable to process the payment.';
                alert(errorMessage);
            }
        }).catch(function () {
            const message = messages.network_error || 'Network error while contacting FluidPay.';
            alert(message);
        }).finally(function () {
            triggers.forEach(function (button) {
                if (button.dataset.originalText) {
                    button.textContent = button.dataset.originalText;
                }

                button.disabled = false;
            });
        });
    }

    function getSaveCardChoice(containerId) {
        const checkbox = document.querySelector('[data-fluidpay-save-for="' + containerId + '"]');

        if (!checkbox) {
            return false;
        }

        return checkbox.checked === true;
    }

    return {
        init: init,
        submit: function (containerId) {
            const data = instances.get(containerId);

            if (!data || !data.instance) {
                return;
            }

            if (typeof data.instance.submit === 'function') {
                data.instance.submit();
            }
        },
        reload: reload,
    };
})();
