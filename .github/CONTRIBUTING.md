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
3. Run Composer to get the dependencies: `cd typo3_console && php ../composer.phar install`

You can run the test suite by executing `.Build/bin/phpunit` when inside the
typo3_console directory. Please note, that some of these tests need a database connection
with a database user that is allowed to create databases. By default user `root` with no password is used.

The name of the created database is `travis_console_test`. You can change the username,
 password and database name by setting the following environment variables accordingly
 prior to executing the phpunit command, on bash, e.g. like this:
 
```bash
export TYPO3_INSTALL_DB_USER=root
export TYPO3_INSTALL_DB_PASSWORD=root
export TYPO3_INSTALL_DB_DBNAME=my_console_test_db
```

Before you submit a pull request with a new or changed command,
make sure you run `Scripts/typo3cms commandreference:render` beforehand
and include the changes in the PR

Contributing policy
-------------------

Fork the project, create a feature branch, and send us a pull request.

To ensure a consistent code base, you should make sure the code follows
the [PSR-2 Coding Standards](http://www.php-fig.org/psr/psr-2/). You can also
run [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) with the
configuration file that can be found in the project root directory.

If you would like to help, take a look at the [list of open issues](https://github.com/TYPO3-Console/typo3_console/issues).
