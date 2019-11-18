# infoCMDB-Testing Suites

## General
infoCMDB uses [Codeception](http://codeception.com/) Testing Framework. 

## Requirements
* Java installed and in PATH
* Selenium installed and running
* Firefox installed 

Use Ansible-Server-Setup to install required software automatically :-)

## Run Tests

In the application root folder execute the following command to start **all tests** with detailed output:
```
php codecept.phar run --steps
```
  
To run only acceptance tests:
```
php codecept.phar run acceptance --steps
``` 

Full example:
```
php codecept.phar run acceptance --html --steps
```

## Reports
### HTML

If you want a beautiful html report append:
```
--html
```
### XML

To generate a xml report append:
```
--xml
```

## Watch Tests via VNC

On Selenium startup a virtual display is created. Codeception uses this display and a browser to perform the defined tests. 
If you like to track the  process you can start a vnc process on the machine where codeception is running:
```
x11vnc -nap -wait 50 -noxdamage -display :1 -forever -o /var/log/x11vnc.log -bg
```

The command will print out on which port the vnc server is running. (Normally 5900)

If you are using a virtual machine you can forward this port to simplify things. (e.g: 5900 --> 5600)


Now you can use your preferred VNC software to view the virtual display.

For example TightVNC via forwarded port:
```
vncviewer localhost::6000
```

Don't worry if you only see a black screen.
Content is only displayed while tests are running ;-) 