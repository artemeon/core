# Coding style

# PHP

All classes should follow the [PSR-2] coding style.

## Naming conventions

Interfaces must have the suffix `Interface` and traits the suffix `Trait`. Normal and abstract classes have no special 
marker.

| Type      | Example       |
|-----------|---------------|
| Class     | News          |
| Abstract  | BasicNews     |
| Interface | NewsInterface |
| Trait     | NewsTrait     |

In general classes should have a descriptive name so that users can easily find the class and understand the function of 
it. Here some example of good class names:

- NewsAdminController
- NewsRecordDeletedListener
- NewsEventProvider
- NewsFeed
- News
- LockManager
- Mail
- FileSystem

The class `NewsAdminController` has the `News` prefix since `AdminController` is difficult to find because almost every 
module has an `AdminController`. The class `LockManager` has no prefix because it is very unlikely that we have many 
lock managers.

Every class must have also a php block containing at least the `@author` and `@since` annotation. The doc block must 
have also a general description of the class. In the following an example class doc block:

```
/**
 * My class description
 *
 * @author foo.bar@kajona.de
 * @since 7.0
 */
```

### Methods

Like defined in PSR-2 method names must be in CamelCase with the first character as lowercase. If the method returns a
boolean value it is recommended to use the is/has prefix to indicate the response type.

In case a class implements an interface it is possible to use the `@inheritdoc` annotation to indicate that the 
description of the interface is also valid for this method. If the method has a special behaviour you should avoid the 
`@inheritdoc` annotation and describe this behaviour in a separate php doc block.

### Variables

Previously we have used a type prefix (`str`, `int`, etc) for each variable to give the developer information about the 
type of a variable. Since this information was not always correct and PHP has introduced scalar type hints we have now 
deprecated this convention. New code should be always written without the type prefix.

As example with the old style we would write the following method signature:

```
public function getStrBar($strFoo)
```

this should now look like: 

```
public function getBar(string $foo): string
```

### Arrays

We use trailing commas for the last entry of an array 

```
$array = [
    'firstEntry',
    'secondEntry',
    ...,
    'nthEntry',
];
```

#### Controller

In general the controller contains methods which are accessible through an url. These methods must be prefixed with 
`action*` i.e. `actionList`. All actions which return a specific data format i.e. JSON or XML should have also the 
prefix `api` i.e. `actionApiList` to indicate that the action is used as API.

### Constants

A constant name in a class consist usually of a group and value name. These values must be concatenated with an 
underscore `[group]_[value]`. Constants must not have a type prefix. Example of good constant names are:

```
const STATE_VALID = 1;
const STATE_OUTDATED = 2;
```

## Namespaces

The namespace must start with a vendor name which is in our case `Kajona`. The next part is the module name which is the
CamelCase name of the module (without the module_ prefix). The rest of the path represents the folder structure inside 
the module. In the following as example some class to file mappings:

| Class     | File          |
|-----------|---------------|
| Kajona\News\System\News   | core\module_news\system\News.php |
| Kajona\News\System\Validators\EmailValidator | core\module_news\system\validators\EmailValidator.php |

## Folder names

It is possible to create arbitrary folder hierarchies inside a module. Folders inside the `system/`
folder should have plural names.

## Comments

Writing comments help other developers to understand a class or method. The following table
shows for which components you should/must write detailed php comments. With comments we mean
a text describing the behaviour, comments which describe the parameters and types must be
always added.

| Priority | Description   | Required          |
|----------|---------------|--------------------
| 1 | interfaces and abstract methods | yes |
| 2 | classes and public methods | yes |
| 3 | protected methods | yes |
| 4 | private methods | only required if the method is complex |

### File header

Every file must contain the following copyright header.

`core/`  

```
<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/
```

`core_agp/`  
`core_*/`  

```
<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/
```

# Javascript

Since AGP 7.1 we have migrated our javascript code base to TypeScript. This helps us to improve the
structure and design of our code base. We still use an AMD loader and all of our type script classes
are converted to a fitting AMD module on build. As coding style we try to follow the type script
coding guidlines: https://github.com/Microsoft/TypeScript/wiki/Coding-guidelines

# Commit messages

General commit message format, based on the [AngularJS] commit message guide:

```
<type>(<module>): <subject>
```

**type:**

* feat: new feature
* fix: bug fix
* docs: changes to documentation / update PHP docs
* lang: update lang properties
* style: adjust code according to our coding style
* refactor: refactoring production code
* test: adding missing tests, refactoring tests; no production code change
* chore: updating grunt tasks etc; no production code change

> This is also the prefix of your git-branch, e.g. `fix/agp-2344_nullpointer-in-contract`

**module:**

Name of the module without the `module_` prefix. You can use * if there isn't a more fitting scope.

**subject:**

This is a short description of the change.



## Examples

```
feat(system): Added new features
docs(prozessverwaltung): Improved calculateNextMepCall php doc
```

[AngularJS]: https://docs.google.com/document/d/1QrDFcIiPjSLDn3EL15IJygNPiHORgU1_OOAqWjiDU5Y/edit
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[JSDoc]: http://usejsdoc.org/

