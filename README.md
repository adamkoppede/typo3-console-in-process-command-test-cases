# Example of executing `helhum/typo3-console` commands in-process

```shell
ddev start
ddev composer install
ddev typo3 install:setup --force --no-interaction
ddev typo3 do-test
```
