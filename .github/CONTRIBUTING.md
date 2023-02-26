# Contributing to TYPO3 Console

Please note that this project is released with a
[Contributor Code of Conduct](http://contributor-covenant.org/version/1/4/).
By participating in this project you agree to abide by its terms.

## Reporting Issues

When reporting issues, please try to be as descriptive as possible, and include
as much relevant information as you can. A step by step guide on how to
reproduce the issue will greatly increase the chances of your issue being
resolved in a timely manner.

For example, if you are experiencing a problem while running one of the
commands, please provide full output of said command including the possibly
show exception trace.

## Security Reports

Please send any sensitive issue to [typo3@helhum.io](mailto:typo3@helhum.io). Thanks!

## Installation from Source

Prior to contributing to TYPO3 Console, you must be able to run the test suite.
To achieve this, you need to acquire the TYPO3 Console source code:

1. Run `git clone https://github.com/TYPO3-Console/typo3_console.git`
2. Download the [`composer.phar`](https://getcomposer.org/composer.phar) executable
3. Run `ddev start` (assuming [ddev](https://ddev.com/) is installed on your system)
4. Run Composer to get the dependencies: `ddev composer update`

You can run the test suite by executing `ddev exec vendor/bin/phpunit`.

Before you submit a pull request with a new or changed command,
make sure you run `ddev exec vendor/bin/typo3 commandreference:render` beforehand
and include the changes in the PR

Contributing policy
-------------------

Fork the project, create a feature branch, and send us a pull request.

To ensure a consistent code base, you should make sure the code follows
the [PSR-2 Coding Standards](http://www.php-fig.org/psr/psr-2/). You can also
run [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) with the
configuration file that can be found in the project root directory.

If you would like to help, take a look at the [list of open issues](https://github.com/TYPO3-Console/TYPO3-Console/issues).
