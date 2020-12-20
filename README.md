Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require <package-name>
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require <package-name>
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    <vendor>\<bundle-name>\<bundle-long-name>::class => ['all' => true],
];
```
### Step 3: Bundle options
Create if not exist ```config/packages/uni_method.yaml```

```yaml
uni_method:
  default_path: '%kernel.project_dir%/config/jsonapi' # path to entities config
  prefix: 'v' # prefix for version forexample /v1/user/
  available: # all available versions
    - 1 # version (subfolder in default_path)
```

### Step 4: Create first version (v1)

```yaml
# config/jsonapi/1/config.yml
entities:
  user:
    class: 'App\Entity\User'
    description: 'Just a user'
    attributes:
      id:
        getter: 'getId()'
        type: integer
      email:
        getter: 'getEmail()'
        type: string
paths:
  -
    item: user
    method: list
```

### Step 5: Enable Bundle routing
```yaml
# config/routes.yaml

app_extra:
  resource: .
  type: extra
```

