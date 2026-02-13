# FluidPay Module

The FluidPay module adds tokenized card/ACH payments to Akaunting invoices and retainers, supports saved payment methods (vault), and handles recurring charges.

## Requirements
- Akaunting core
- FluidPay API keys (`pub_` for client-side, `api_` for server-side requests)

## Installation
1. Install/enable the module in Akaunting.
2. Run migrations:
   ```bash
   php artisan module:migrate fluidpay
   ```
3. (Optional) Seed email templates if upgrading:
   ```bash
   php artisan module:install fluidpay <company_id>
   ```

## Configuration
Navigate to **Settings → Apps → FluidPay**:
- **Public Key**: `pub_...`
- **Private Key**: `api_...` (required for server-side charges and vault)
- **Environment**: Sandbox or Production

Changing the environment may require regenerating keys in the FluidPay dashboard.

## Recurring Payments
When a customer’s payment method is saved, recurring invoices will attempt to charge the default vault payment method automatically. Failed charges trigger the “Failed recurring payment” email template.

## Troubleshooting
- **Unauthorized**: Ensure the private key starts with `api_` and matches the selected environment.
- **Missing vault table**: run `php artisan module:migrate fluidpay`.
- **Tokenizer not loading**: confirm CSP allows the FluidPay base URL and tokenizer script.

## Support
Use the `x-correlation-id` response header from FluidPay API calls when contacting FluidPay support.
