V = {
    URLBASE: document.getElementsByTagName('base')[0].href,

    /**
     * Gets the first element who matches the selector.
     */
    e: function (selector)
    {
        return  document.querySelector(selector)
    },

    /**
     * Gets all the elements matching the selector.
     */
    es: function (selector)
    {
        return  document.querySelectorAll(selector)
    },

    /**
     * Gets an element by its id attribute.
     */
    id: function (id)
    {
        return document.getElementById(id)
    },

    /**
     * Gets the first element by its name attribute.
     */
    n: function (name)
    {
        return document.getElementsByName(name)[0]
    },

    /**
     * Gets all the elements by its name attribute.
     */
    ns: function (name)
    {
        return document.getElementsByName(name)
    },

    /**
     * Gets the first nodes by tag name
     */
    t: function(name)
    {
        return document.getElementsByTagName(name)[0]
    },

    /**
     * Gets all the elements by its tag name
     */
    ts: function(name)
    {
        return document.getElementsByTagName(name)
    },

    /**
     * Creates a new element.
     */
    c: function (element, attributes, content) {
        var el = document.createElement (element);
        var a = 0

        // Si attributes no es objeto, entonces es el contenido.
        if (typeof attributes != "object") {
            content = attributes
            attributes = false
        }


        if (attributes) for (a in attributes) {
            if (typeof attributes[a] == "function") {
                el[a] = attributes[a]
            } else {
                el.setAttribute(a, attributes[a])
            }
        }

        if (content instanceof HTMLElement) {
            el.append(content)
        } else if (content) {
            el.innerHTML = content
        }

        return el
    },

    /**
     * Creates a series of TDs inside a TR with each array element.
     */
    tr: function (data, td_opts, tr_opts)
    {

        tr = this.c ('tr', tr_opts)
        for (id in data) {

            if (td_opts && id in td_opts) {
                opts = td_opts[id]
            } else {
                opts = {}
            }

            td = this.c ('td', opts, data[id])

            tr.appendChild (td)
        }

        return tr
    },

    /**
     * Redirects to another URL with optional method and payload.
     */
    redirect: function (url, method, variables) {
        var method = typeof method !== 'undefined' ? method : 'get'

        // Si url es un objeto, entonces son las variables, y la
        // url es esta misma url
        if (typeof url === 'object') {
            variables = url
            url = window.location
        }


        // Si es GET, y no hay variables, super simple
        if (method == "get" && typeof variables == "undefined") {
            window.location.href = url
        } else {

            var form = this.c('form', {
                method: method,
                action: url
            })

            // En POSTS, añadimos el token CSRF
            if (method == 'post') {
                if (typeof variables == 'undefined') {
                    variables = {}
                }
                variables['__VENDIMIA_SECURITY_TOKEN'] =
                    V.e('meta[name=vendimia-security-token]').content
            }

            if (variables) for (id in variables) {
                // Si el valor es un array, entonces tenemos
                // que duplicar
                if (Array.isArray ( variables[id]) ) {
                    for (element in variables[id]) {
                        value = variables[id][element]

                        el = this.c('input', {
                            type: 'hidden',
                            name: id + "[]",    // Para PHP
                            value: value,
                        })
                        form.appendChild (el)
                    }
                } else {
                    el = this.c('input', {
                        type: 'hidden',
                        name: id,
                        value: variables[id]
                    })
                    form.appendChild (el)
                }
            }


            // Para Firefox: añadimos el formulario al body
            document.body.appendChild(form);
            form.submit()
        }
    },

    /**
     * Realiza un post. Es azucar sintáctico de redirect()
     */
    post: function (url, variables)
    {
        this.redirect(url, 'post', variables)
    },

    /**
     * Hace una confirmación antes de redirigir
     */
    redirect_confirm: function (message, url, method, vars) {
        if (window.confirm(message)) {
            this.redirect (url, method, vars);
        }
    },

    /**
     * Hace una confirmación antes de redirigir usando post
     */
    post_confirm:  function (message, url, vars) {
        if (window.confirm (message)) {
            this.redirect (url, 'post', vars);
        }
    },


    /**
     * Obtiene la información de un cookie
     */
    get_cookie: function (cookie)
    {
        result = document.cookie.match('(^|;)\\s*' + cookie + '\\s*=\\s*([^;]+)')
        return result ? result.pop() : ''
    },

    /**
     * Obtiene las coordenadas absolutas de un elemento, con respecto al documento
     */
    xy: function (control)
    {
        if (typeof control == "string") {
            control = this.id(control)
        }

        var r = control.getBoundingClientRect()

        return [r['left'] + window.pageXOffset, r['top'] + window.pageYOffset]
    },

    /**
     * Wrapper to fetch() method to include AJAX headers
     */
    fetch: function(resource, init = {}) {
        if (!init.headers) {
            init.headers = new Headers();
        }
        init.headers.set('X-Requested-With', 'ajax')
        init.headers.set('X-Csrf-Token', V.e("meta[name=vendimia-security-token]").content)

        return fetch(resource, init)
    },
}
