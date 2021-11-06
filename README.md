# AdmiralCheck

## What is this?

This is a simple project that cross-checks the list of Admiral domains maintained at https://github.com/jkrejcha/AdmiraList. It verifies each domain in the list and outputs a few pieces of information in a tab delimited file to help weedle out dead domains and potentially idenfity other changes.

## Usage
clone the repo (incl submodules), open up your favorite terminal and run `php main.php`. That will read the input from jkrejcha's repo and generate output in a file called `results.txt`. 

The structure of the results file is as follows:
- http_code: what was the web server response for the domain.
- txt | n/a: was the string "This domain is used by digital publishers to control access to copyrighted content " present on the webpage?
- hash: An md5 hash of the webpage to find differences between domains
- the URL that responded ()

## Things to do

For one there's not necessarily a correspondence between the input and output file. The url that responded will be after any redirects. Should be simple to add.

Ultimately it would be great if the output could be used to help generate a PR on [AdmiraList](https://github.com/jkrejcha/AdmiraList).

## Contributing

Pull requests are accepted and are very welcome! Your help is really appreciated. :)