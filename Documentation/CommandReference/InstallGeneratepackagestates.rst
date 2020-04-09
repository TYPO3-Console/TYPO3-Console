
.. include:: ../Includes.txt



.. _typo3_console-command-reference-install-generatepackagestates:

The following reference was automatically generated from code.


=============================
install:generatepackagestates
=============================


**Generate PackageStates.php file**

Generates and writes `typo3conf/PackageStates.php` file.
Goal is to not have this file in version control, but generate it on `composer install`.

Marks the following extensions as active:

- Third party extensions
- All core extensions that are required (or part of minimal usable system)
- All core extensions which are provided with the `--framework-extensions` argument.
- In composer mode all composer dependencies to TYPO3 framework extensions are detected and activated by default.

To require TYPO3 core extensions use the following command:

`composer require typo3/cms-foo "*"`

This updates your composer.json and composer.lock without any other changes.

**Example:**

  `typo3cms install:generatepackagestates`



Options
~~~~~~~

`--framework-extensions`
   TYPO3 system extensions that should be marked as active. Extension keys separated by comma.

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: array ()

`--excluded-extensions`
   Extensions which should stay inactive. This does not affect provided framework extensions or framework extensions that are required or part as minimal usable system.

- Accept value: yes
- Is value required: yes
- Is multiple: no
- Default: array ()

`--activate-default`
   (DEPRECATED) If true, `typo3/cms` extensions that are marked as TYPO3 factory default, will be activated, even if not in the list of configured active framework extensions.

- Accept value: no
- Is value required: no
- Is multiple: no
- Default: false





