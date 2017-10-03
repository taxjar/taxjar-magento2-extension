# Magento 2 Sales Tax Extension by TaxJar

Simplify your sales tax with live checkout calculations and zip-based backup rates from [TaxJar](http://www.taxjar.com).

To get started, check out our [M2 extension guide](http://www.taxjar.com/guides/integrations/magento2/)!

## Getting Started

Download the extension as a ZIP file from this repository or install our module with [Composer](https://getcomposer.org/) using the following command:

```
composer require taxjar/module-taxjar
```

If you're installing the extension manually, unzip the archive and upload the files to `/app/code/Taxjar/SalesTax`. After uploading, run the following [Magento CLI](http://devdocs.magento.com/guides/v2.0/config-guide/cli/config-cli-subcommands.html) commands:

```
bin/magento module:enable Taxjar_SalesTax --clear-static-content
bin/magento setup:upgrade
bin/magento setup:di:compile
```

These commands will enable the TaxJar extension, perform necessary database updates, and re-compile your Magento store. From there, you'll want to run through the pre-import checklist and set everything up using our [extension guide](http://www.taxjar.com/guides/integrations/magento2/)).

## Tests

To run our integration tests for checkout calculations, clone the repository into your local instance of Magento 2. You'll need an active TaxJar API token (preferably a test account) to run these tests.

```
git clone https://github.com/taxjar/taxjar-magento2-extension.git app/code/Taxjar/SalesTax
```

Backup or rename your existing `phpunit.xml` under `dev/tests/integration`. Copy the `phpunit.xml file` in the TaxJar module under `app/code/Taxjar/SalesTax/Test/Integration`:

```
cp app/code/Taxjar/SalesTax/Test/Integration/phpunit.xml dev/tests/integration/phpunit.xml
```

Rename `install-config-mysql.php.dist` to `install-config-mysql.php` under `dev/tests/integration/etc`. Make sure Magento has access to a MySQL database for running integration tests.

Copy `Test/Integration/credentials.php.dist` to `credentials.php` in the same directory and add your TaxJar API token:

```
cp app/code/Taxjar/SalesTax/Test/Integration/credentials.php.dist app/code/Taxjar/SalesTax/Test/Integration/credentials.php
```

Finally, run the TaxJar test suite using PHPUnit:

```
vendor/bin/phpunit -c ~/OSS/magento2/dev/tests/integration/phpunit.xml --testsuite “Taxjar”
```

Notice that the configuration flag should include the full path to `phpunit.xml`.

## License

TaxJar's Magento 2 module is released under the [Open Software License 3.0](https://opensource.org/licenses/OSL-3.0) (OSL-3.0).

## Support

If you find a bug in our extension, [open a new issue](https://github.com/taxjar/taxjar-magento2-extension/issues/new) right here in GitHub. For general questions about TaxJar or specific issues with your store, please [contact us](http://www.taxjar.com/contact/) after going through our extension guide.
