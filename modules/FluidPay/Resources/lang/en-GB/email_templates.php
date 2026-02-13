<?php

return [
    'fluidpay_recurring_failed_customer' => [
        'subject' => 'Payment failed for {invoice_number} invoice',
        'body' => 'Dear {customer_name},<br /><br />We attempted to process the recurring payment for <strong>{invoice_number}</strong> but it failed. Please update your payment method using the following link: <a href="{invoice_guest_link}">{invoice_number}</a>.<br /><br />If you have any questions, please contact us.<br /><br />Best Regards,<br />{company_name}',
    ],
];
