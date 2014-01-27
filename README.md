Mage Translate
==============

This set of scripts lets you export / import the translation files of your module or theme for an easier processing inside a stylesheet editor.

## Installation

The files should lie in a specific directory of the "shell" folder, in your magento installation.
For instance : magento/shell/translate/

## Usage

There are two PHP files available : import.php and export.php.

### Export

Just provide the locale directory to parse and the name of the csv file present in each sub-locale directory.

Examples :

```
php export.php --file Mage_Catalog.csv --source ../../app/locale
php export.php --file translate.csv --source ../../app/design/frontend/package/theme/locale
```

For the usage details and more parameters use :

```
php export.php help
```

### Import

Same logic as the export but the other way around. Provide the contributed csv file (which was originally exported) and the locale folder of destination.

The import will by default keep all translation keys that are present in the destination locale folder but not in the imported file. This can be overriden in the options.

```
php import.php --file Mage_Catalog.csv --destination ../../app/locale
php import.php --file translate.csv --destination ../../app/design/frontend/package/theme/locale
```

For the usage details and more parameters use :

```
php export.php help
```

