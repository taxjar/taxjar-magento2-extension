# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2018-10-16
- Admin notifications for TaxJar extension updates now available.
- Add calculation logging when debug mode is enabled.
- Fix partial refund transaction sync for credit memos with adjustments.
- Fix extension conflict with admin frontend scripts.

## [1.0.3] - 2018-07-19
- Fix "None" tax class product exemptions when syncing transactions.

## [1.0.2] - 2018-07-02
- Fix discounts applied to shipping amounts for calculations.
- Directly apply shipping discount to order-level `shipping` param and remove shipping discount line item when syncing transactions.
- Validate zip codes by country using native patterns for calculations to reduce API request volume.
- Backfill transactions by `created_at` instead of `updated_at`.
- Fix transaction sync error for PHP < 7.

## [1.0.1] - 2018-04-21
- Fix error in nexus edit form.
- Update translation dictionary.

## [1.0.0] - 2018-04-20
- **Multi-store calculations and transaction sync.** Nexus addresses can be assigned to specific stores in Magento for calculations. Multiple API tokens can be used to calculate sales tax and sync transactions to TaxJar. After upgrading, please review your TaxJar configuration at the store level and test your checkout.
- **Multi-website shipping origins.** TaxJar now uses different shipping origins by website for calculations and reporting / filing. After upgrading, please review your shipping origins at the website level and test your checkout.
- **Transaction sync CLI command.** Developers can backfill transactions into TaxJar using the Magento CLI. To get started, [read the documentation](#cli-commands) on using `bin/magento taxjar:transactions:sync`.
- Improve full tax summary at checkout with tax amounts and rates per jurisdiction.
- Calculate line item tax amounts in Magento using the API rate for more precise calculations.
- Tweak transaction sync registry code to improve compatibility with 3rd party extensions.
- Increase timeout to 30 seconds for general API requests.

## [0.7.6] - 2017-11-14
- Fix refund transaction sync after creating a new credit memo.
- Fix gift card exemptions in Magento EE.
- Require a unique tax class for backup shipping rates.
- Support split databases when installing the module.

## [0.7.5] - 2017-10-03
- Composer support for Magento 2.2.
- Fix transaction sync for completed virtual orders at checkout.
- Fix debug mode error when TaxJar account has no nexus states.
- Fix minor client exception syntax issue.
- Fix backup rates field comment typo.
- Update specs for Magento 2.2 & PHPUnit 6.

## [0.7.4] - 2017-08-15
- Fully exempt tax for products with tax class set to "None".
- Fix child item quantities for bundle line items when parent quantity is > 1.
- Fix calculations for fixed price bundle products.
- Add note to "TaxJar Exempt" field for customer exemptions.

## [0.7.3] - 2017-08-02
- Ensure non-US, non-USD orders are filtered during transaction backfill process.
- Pass shipping discounts as a separate line item when syncing transactions.
- Prevent duplicate order comments and total refund amounts when syncing refunds.
- Fix line item IDs for credit memo line items when syncing refunds.
- Reduce configurable / bundle product children line items to base products when syncing refunds.
- Hide sync dates for orders and credit memos if empty.

## [0.7.2] - 2017-05-02
- Fix transaction sync for duplicate configurable product line items with different simple products.

## [0.7.1] - 2017-03-24
- Require Magento 2.1 or later for real-time transaction syncing.
- Update translation dictionary.

## [0.7.0] - 2017-03-23
- **Transaction sync for automated sales tax reporting and filing.** Orders and credit memos can now be synced to TaxJar with a 30 day reporting trial or paid subscription.

## [0.6.4] - 2017-03-17
- Fix order creation error with multiple bundle or configurable products.

## [0.6.3] - 2016-11-18
- Support "Apply Customer Tax" configuration setting for before and after discount calculations.

## [0.6.2] - 2016-10-10
- Fix customer exemption check for new customers during admin orders.

## [0.6.1] - 2016-10-07
- Fix tax code error for products without a tax class or set to "None".

## [0.6.0] - 2016-10-04
- Customer tax class management for SmartCalcs customer exemptions now available under **Stores > Customer Tax Classes**.
- Support gift card exemptions in Magento EE.
- Import TaxJar product categories immediately after connecting to TaxJar.
- Fix cron issue when syncing backup rates.
- Tweak version handling in debug mode and connect popup.

## 0.5.0 - 2016-06-30
- **Initial release of our Magento 2 extension.** Sales tax calculations at checkout with backup zip-based rates powered by TaxJar. Supports product exemptions, shipping taxability, sourcing logic, and international calculations in more than 30 countries.
- **Special promo sales tax calculations for Magento merchants.** Existing M2 beta users must upgrade to this version to receive special promo calculations at checkout using our new API endpoint.

[Unreleased]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.0.3...v1.1.0
[1.0.3]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.7.6...v1.0.0
[0.7.6]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.7.5...v0.7.6
[0.7.5]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.7.4...v0.7.5
[0.7.4]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.7.3...v0.7.4
[0.7.3]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.7.2...v0.7.3
[0.7.2]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.7.1...v0.7.2
[0.7.1]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.7.0...v0.7.1
[0.7.0]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.6.4...v0.7.0
[0.6.4]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.6.3...v0.6.4
[0.6.3]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.6.2...v0.6.3
[0.6.2]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.6.1...v0.6.2
[0.6.1]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/taxjar/taxjar-magento2-extension/compare/v0.5.0...v0.6.0
