# AdmiralCheck

## What is this?

This is a simple project that cross-checks the list of Admiral domains maintained at https://github.com/jkrejcha/AdmiraList. It verifies each domain in the list and outputs a few pieces of information in a tab delimited file to help weedle out dead domains and potentially idenfity other changes.

## Usage
Clone the repo (incl submodules), open up your favorite terminal and run `php main.php`. That will read input from jkrejcha's repo and generate two output files:

* _AlCheckOut_analysis.txt_: a full dump of what we want to keep for analysis
* _AlCheckOut_pr_dead.txt_: same as `AdmiraList/AdmiraList.txt` but pruned of dead domains.

The structure of the _analysis_ file is as follows:

* _http_code_: what was the web server response for the domain.
* _txt | n/a_: was the string "This domain is used by digital publishers to control access to copyrighted content " present on the webpage?
* _hash_: An md5 hash of the webpage to find differences between domains.
* _domain_: the domain that was checked.

## Things to do

Maybe generate an abstract version of the analysis file with only the unique hashes and an example domain thereof.

## Contributing

Pull requests are accepted and are very welcome! Your help is really appreciated. :)