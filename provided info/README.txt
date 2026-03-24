We have a page at https://www.bimmer-tech.net/carplay/software-download. This is a firmware download page for one of our products.

The product has a few hardware and software variants. Each variant uses different firmware, and it is very important that customers only install the correct firmware on their device. If they install the wrong firmware, it can destroy the device. To reduce the chance of any mistakes, all users must enter their current software and hardware versions. We match that to the correct firmware download link.

This directory includes the three files that make that page work:

* softwareversions.json -- a list of all software versions
* ConnectedSiteController.php -- the function for the API endpoint at https://www.bimmer-tech.net/api2/carplay/software/version
* software.vue -- the front end of the page at https://www.bimmer-tech.net/carplay/software-download

At the moment, when a new firmware version becomes available, somebody at the company needs to ask a developer to change these files to add the new software and update the code for the new patterns. It is easier if they can manage software versions themselves through an admin panel.

Reimplement this page and API as a Symfony app. There must be an admin panel where our team can add and manage software versions, and a page where customers can enter all their device details and download the correct file.

REQUIREMENTS

* All outcomes (download links, errors etc.) must be identical to what we get from the page at the moment. You can use our existing page to confirm.
* People at our company have limited technical skills. It must be as easy as possible to add and manage software versions accurately.
* Please provide the required data to make your app run like the existing page immediately, e.g. if you use a SQL database for the software versions, include the queries to populate it with all the existing software versions.
* Include a README how you want us to build and run your app, any system requirements there are, and instructions how to manage the software versions. We will run your app locally on a Linux machine following your instructions.

We are not able to answer any additional questions about this page, what it does and why. We also want to see how well you are able to get to grips with unfamiliar features in our app.
