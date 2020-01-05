A simple tool to get statistics from the PRM-output from the german pirate party.

Using jQuery and PHP Desktop.

# Installation
0. Only Windows OS is supported.
1. Download this repository.
2. Double click the `PRM-Aufbereiter-START` executable.

# How to use
Copy CSV data into the input field and click the button. It will calculate the statistics and output them in a nice Markdown formated way. 

You can change your settings by clicking on the top left corner. Your settings will be stored permanently.

# Known issues
## Break lines in the CSV file
Break lines `\n` in the CSV file that are not ment as a CSV line seperator will be misinterpreted. Please make sure you DO NOT HAVE such break lines in your file. 

## Some statistics does not get calculated
Make sure that youre source data contains these information and check your settings. 

The statistics depend on the colum names used in the PRM from the german pirate party.