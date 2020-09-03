# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.5.7] - 2020-09-03
- Improve the display of the admin configuration page

## [1.5.6] - 2020-08-19
- Fix support for table name prefixes used in admin grids
- Fix requiring TaxJar with composer before installing Magento (thanks @JosephMaxwell)
- Fix a fatal error that occasionally occurred when adding products to the cart (2.4 only)

## [1.5.5] - 2020-07-31
- Update composer requirements to include Magento 2.4

## [1.5.4] - 2020-06-25
- Add support for sandbox API access
- Fix an issue when building the to/from address street

## [1.5.3] - 2020-06-12
- Fix sorting by "Synced to TaxJar" column on admin grids (thanks @bjwirgau)
- Fix for broken code compilation in PHP 5.6
- Improve integration testing for order and refund transactions

## [1.5.2] - 2020-05-28
- Improve accuracy when purging backup rates
- Improve performance when loading admin order/creditmemo grids (thanks @Maikel-Koek)
- Improve calculateRate performance when extension is disabled (thanks @Maikel-Koek)

## [1.5.1] - 2020-05-15
- SmartCalcs Sales Tax API is now simplified to TaxJar Sales Tax API or TaxJar API
- Added integration tests for orders and refunds (adjustment fees and bundled products)
- Fix for refund adjustments at the line item level
- Fix for partial refunds of bundled products

## [1.5.0] - 2020-05-01
- Fix a bug where customer_id was passed as 0 instead of an empty string
- Update product tax categories to remove the plus_only flag

## [1.4.10] - 2020-04-16
- Fix transaction sync not respecting website/store scope when backfilling transactions
- Fix the max discount per line item ignoring line item quantities
- Fix international address validation for logged in customers with a saved address

## [1.4.9] - 2020-02-21
- Fix typo in use statement in class SyncProductCategoriesCommand.

## [1.4.8] - 2020-02-20
- Update product tax categories by syncing them monthly. Also adds a "Sync from TaxJar" button and CLI command.
- Update the gift card product tax category to be more accurate.
- Add a custom user agent string and Referer header to all API requests.
- Fix sorting nexus addresses by store view.

## [1.4.7] - 2020-02-04
- Fix customer save observer when no default shipping address is present

## [1.4.6] - 2020-02-03
- Improve support for M2ePro users when syncing multi-channel orders to TaxJar
- Improve storing product tax categories by storing them in their own table
- Fix several issues when issuing multiple credit memos for unshipped orders

## [1.4.5] - 2020-01-10
- Fix loading customer address data during admin massactions by using the CustomerRepository
- Fix floating point comparisons when using json_encode to cache api requests
- Fix error where discount can be less than the unit price
- Remove unused reporting_access promo code

## [1.4.4] - 2019-11-05
- Fix code compilation in Magento version 2.1

## [1.4.3] - 2019-11-01
- Replace calls to serialize/unserialize with JSON encode/decode.

## [1.4.2] - 2019-09-20
- Improve address validation error handling when missing address data.
- Improve support for deploying production mode when address validation is enabled.
- Fix the `hasNexus` check from succeeding if the region is empty.
- Fix Enterprise and B2B gift card exemptions not being applied during checkout.
- Update namespace for PHPUnit 6.x compatibility.

## [1.4.1] - 2019-07-31
- Products set to "None" tax class will no longer pass a fully exempt `99999` tax code for calculations and transaction sync in order to support AutoFile.
- Add description to product tax class field explaining that a TaxJar category is required to exempt products from sales tax.
- Fix address validation error during backend order creation process in admin panel.

## [1.4.0] - 2019-07-11
- **Customer exemption support for reporting / filing.** Sync wholesale / resale and government exempt customers for sales tax calculations, reporting, and filing. Exempt customers individually or in bulk using the customer admin grid.
- Customer tax class "TaxJar Exempt" setting is now discouraged in favor of customer-specific exemptions. This setting will continue to skip tax calculations if needed, but we highly recommend updating your customers directly and choosing an exemption type for reporting / filing support.
- Fix front-end form validation after saving customers in admin panel when address validation is enabled.
- Fix JS error after a customer attempts to add / edit an address when address validation is disabled.
- Fix product tax class scoping to specific website when syncing transactions.
- Fix PHP warning when iterating over an empty array during tax calculation.

## [1.3.0] - 2019-05-10
- **Address validation for TaxJar Plus.** Validate and suggest shipping addresses in the checkout process, customer address book, backend orders, and backend customer addresses. Improves accuracy of sales tax calculations.

## [1.2.1] - 2019-02-19
- Create /var/tmp if the directory does not exist on Magento Commerce Cloud for backup rates.
- Fix broken product tax classes link under "Getting Started" in the TaxJar configuration.

## [1.2.0] - 2019-01-24
- Multi-store transaction sync can now be enabled on a per-store or per-website basis.
- Make nexus address fields (street, city, zip) optional for remote sellers / economic nexus.
- Sync individual child products in dynamic priced bundle products to support item-specific product tax codes.
- Fix empty line items issue when syncing partial refund adjustments.
- Fix error when syncing nexus addresses with an incomplete TaxJar business profile.
- Rename empty product category label from "None" to "Fully Taxable".

## [1.1.1] - 2018-11-29
- Composer support for Magento 2.3.
- Fix logger typo for calculation exception logging.
- Fix order / credit memo sync date render issue in admin grids.

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

[Unreleased]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.5.7...HEAD
[1.5.7]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.5.6...v1.5.7
[1.5.6]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.5.5...v1.5.6
[1.5.5]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.5.4...v1.5.5
[1.5.4]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.5.3...v1.5.4
[1.5.3]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.5.2...v1.5.3
[1.5.2]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.5.1...v1.5.2
[1.5.1]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.5.0...v1.5.1
[1.5.0]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.10...v1.5.0
[1.4.10]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.9...v1.4.10
[1.4.9]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.8...v1.4.9
[1.4.8]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.7...v1.4.8
[1.4.7]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.6...v1.4.7
[1.4.6]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.5...v1.4.6
[1.4.5]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.4...v1.4.5
[1.4.4]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.3...v1.4.4
[1.4.3]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.2...v1.4.3
[1.4.2]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.1...v1.4.2
[1.4.1]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.4.0...v1.4.1
[1.4.0]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.1.1...v1.2.0
[1.1.1]: https://github.com/taxjar/taxjar-magento2-extension/compare/v1.1.0...v1.1.1
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
