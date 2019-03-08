# Vendimia Framework

[![Join the chat at https://gitter.im/vendimia](https://badges.gitter.im/vendimia/vendimia.svg)](https://gitter.im/vendimia)

**Vendimia** is a PHP framework for fast developing web applications using the MVC design pattern.

Vendimia doesn't requiere root privileges for installing it, so a Vendimia application runs smoothly on a shared server like cPanel. You can even have several Vendimia applications in separated directories, each one with its own Vendimia installation.

**WARNING**: Vendimia is in a **very-very-alpha** stage of development. Many parts are incomplete, and the API can change in any moment. For now, it's not suitable for production environments. Use it at your own risk.

# Requirements

* PHP 7+ with `CLI`, and modules `mbstring` and `fileinfo` installed.

# Quickstart

* Clone this repo inside a directory included in the [include_path](http://php.net/manual/en/ini.core.php#ini.include-path) PHP directive.

```
git clone git@github.com:vendimia/vendimia.git
```
If you doesn't have access to any of the `include_path` directories nor alter the PHP directive value, just clone it anywhere, and set the environment variable VENDIMIA_BASE_PATH with its full path:

```
cd /home/oliver
git clone git@github.com:vendimia/vendimia.git
export VENDIMIA_BASE_PATH=/home/oliver/vendimia
```

* Set up access to the `vendimia` administration script.

You can either create a symbolic link inside a directory listed in the `PATH` environment variable:

```
ln -s /path/to/vendimia/bin/vendimia ~/bin
```

(assuming `~/bin` is in your `PATH`). Or you can alter the `PATH`:

```
export PATH=$PATH:/path/to/vendimia/bin
```

* Create a new Vendimia project with the command `vendimia init`.

```
vendimia init myapp
```

This will create a `myapp` directory with a basic project directory structure.

* Launch a development server:

```
cd myapp
vendimia server
```

Point your web browser to http://localhost:8888 and you're ready to go.

# Documentation

Please visit http://docs.vendimia.in/. It's work-in-progress, too. You can also ask me any questions on the [Gitter chat](https://gitter.im/vendimia).

# About the author

My name is [Oliver Etchebarne](http://drmad.org), from [Ica](https://en.wikipedia.org/wiki/Ica,_Peru), [Perú](https://en.wikipedia.org/wiki/Peru). I started (indirectly) coding this framework in the year 2000, building several libraries for access the database, html forms rendering and validating, etc.

*Circa* year 2012 I begun to find another language for creating web apps, disappointed about the *status quo* of PHP at that time. I tried Django and Rails, but neither really convinced me. Next year, I "discovered" that PHP was *[less ugly](https://drmad.org/blog/10-cosas-que-probablemente-no-sabias-de-php.html)*, so I gave it a new try, updating and integrating all my libraries (and creating new ones inspired on Django y Rails :grin:) in this framework base.

Two years ago (2014), I decided to polish all the libraries for publishing the framework as an Open Source project, and begun to close the gaps in the integration of every library, and gave its name "Vendimia". This year (2016) I gave it the last *overhauling* using the [PHP-FIG](http://www.php-fig.org/) guidelines, and updating the objects and classes for more loose-coupling between them, and implementing some other new coding paradigm.

And on September 17, 2016, to celebrate the [Software Freedom Day](http://www.softwarefreedomday.org/), I finally published it to GitHub :smiley: .

A more long (and in spanish) version of this story can be found in my blog: https://drmad.org/blog/vendimia-framework.html.

Hope to hear from you soon!
