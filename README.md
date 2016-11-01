OS2Web CPR Filter
=============================

Description
-----------
Drupal text filter searching texts for CPR-numbers using the Drupal core
filter API and replacing them.

The filter supports:

* Modulus11 validation of the found CPR-numbers to avoid replacing
non modulus11 valid numbers. 
* Date validation on CPR-numbers failing the modulus11 validation.
* Replacing of all found numbers in the format XXXXXX-XXXX.

The replacement value can be edited in the filter settings. By default is it
XXXXXX-XXXX.

Installation
------------
This module should reside in the modules directory of the installation,
most commonly profiles/profile-name/modules, but alternately in 
modules (This could be for development purposes).

Remember to go and enable the filter "OS2Web CPR-number filter" in all
your text formats, and configure the filter settings to your needs. 

This module can also be installed with drush make in your install profile.

Additional Info
---------------
This repository should be governed using Git Flow. for more information see
http://nvie.com/posts/a-successful-git-branching-model/
