# typo3-sort-extensions composer plugin

## install & configuration

replace `andersundsehr/aus_project` with your site-package package name.
````sh
composer config extra.andersundsehr/typo3-sort-extensions.site-package andersundsehr/aus_project
composer req andersundsehr/typo3-sort-extensions
``
## what does it do

This plugin automatically copies all externally* required TYPO3 Extensions to the site-package `require` section.  
So the externally* required Extensions will always be loaded before locally* installed TYPO3 Extensions.  
It also requires the site-package in all the locally* installed TYPO3 Extensions, so they are loaded after the site-package and all the externally* required TYPO3 Extensions.  

This solves the problem that sometimes you install/remove an extension and the order changes so that e.g. TCA/Overrides no longer work as before.

## Example before :
`root composer.json`:
````json
{
  "repositories": [
    {
      "type": "path",
      "url": "extensions/*",
      "canonical": false,
      "options": {
        "reference": "none"
      }
    }
  ],
  "require": {
    "andersundsehr/aus_project": "@dev",
    "andersundsehr/aus_example": "@dev",
    "pluswerk/minify": "^3.0.1",
    "typo3/cms-core": "^11.5.4"
  },
  "extra": {
    "andersundsehr/typo3-sort-extensions": {
      "site-package": "andersundsehr/aus_project"
    }
  }
}
````
`aus_project composer.json`:
````json
{
  "require": {
  }
}
````
`aus_example composer.json`:
````json
{
  "require": {
  }
}
````


## after:
`root composer.json`:
````json
{
  "repositories": [
    {
      "type": "path",
      "url": "extensions/*",
      "canonical": false,
      "options": {
        "reference": "none"
      }
    }
  ],
  "require": {
    "andersundsehr/aus_project": "@dev",
    "andersundsehr/aus_example": "@dev",
    "andersundsehr/group_access": "^1",
    "pluswerk/minify": "^3.0.1",
    "typo3/cms-core": "^11.5.4"
  },
  "extra": {
    "andersundsehr/typo3-sort-extensions": {
      "site-package": "andersundsehr/aus_project"
    }
  }
}
````
`aus_project composer.json`:
````json
{
  "require": {
    "pluswerk/minify": "*",
    "andersundsehr/group_access": "*"
  }
}
````
`aus_example composer.json`:
````json
{
  "require": {
    "andersundsehr/aus_project": "*"
  }
}
````

# with ♥️ from anders und sehr GmbH

> If something did not work 😮  
> or you appreciate this Extension 🥰 let us know.

> We are hiring https://www.andersundsehr.com/karriere/

