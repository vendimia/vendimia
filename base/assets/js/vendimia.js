V = {
    URLBASE: document.getElementsByTagName ('base')[0].href,

    /**
     * Obtiene el primer elemento que encaje con el selector
     */
    e: function (selector) {
        return  document.querySelector (selector)
    },

    /**
     * Obtiene todos los elementos que encajen con los selectores
     */
    es: function (selectors) {
        return  document.querySelectorAll (selectors)
    },

    /**
     * Obtiene el elemento con ID 'id'
     */
    id: function (id) {
        return document.getElementById (id)
    },

    /**
     * Obtiene el primer elemento por su nombre
     */
    n: function (name) {
        return document.getElementsByName(name)[0]
    },

    /** Obtiene todos los elementos por su nombre
     *
     */
    ns: function (name) {
        return document.getElementsByName(name)
    },

    /**
     * Gets the first nodes by tag name
     */
    t: function(name) {
        return document.getElementsByTagName(name)[0]
    },
    /**
     * Crea un nuevo elemento
     */
    c: function (element, attributes, content) {
        var el = document.createElement (element);
        var a = 0
        
        // Si llamamos sin atributos
        if (typeof attributes == "string") {
            content = attributes 
            attributes = false
        }


        if (attributes) for (a in attributes) {

            if (typeof attributes[a] == "function") 
                el[a] = attributes [ a ]
            else
                el.setAttribute (a, attributes [ a ])
        }

        if (content) {
            el.innerHTML = content 
        }

        return el
    },

    /** 
     * Crea una suceción de TDs dentro de un TR con cada elemento del array
     */
    tr: function (data, td_opts, tr_opts) {

        tr = this.c ('tr', tr_opts)
        for (id in data) {

            if (td_opts && id in td_opts) {
                opts = td_opts[id]
            }
            else {
                opts = {}
            }

            td = this.c ('td', opts, data[id])

            tr.appendChild (td)
        }

        return tr
    },

    /**
    * Redirige a otra URL, opcionalmente con variables. Si el 
    * método es POST, crea un formulario.
    */
    redirect: function (url, method, variables) {
        var method = typeof method !== 'undefined' ? method : 'get'

        // Si no tiene un scheme, le añadimos la URL base
        /*if (url.substr (0, 7) != 'http://')
            url = URLBASE + url;*/

        // Si es GET, y no hay variables, super simple
        if (method == "get" && typeof variables == "undefined") {
            window.location.href = url
        }
        else {
            var form = this.c('form', {
                method: method,
                action: url
            })

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
                }
                else {
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
    post: function (url, variables) {
        // Si url es un objeto, entonces son las variables, y la
        // url es esta misma url
        if ( typeof url === 'object') {
            variables = url
            url = window.location
        }

        this.redirect (url, 'post', variables)
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
    get_cookie: function (cookie) {
        result = document.cookie.match('(^|;)\\s*' + cookie + '\\s*=\\s*([^;]+)')
        return result ? result.pop() : ''
    },

    /**
     * Obtiene las coordenadas absolutas de un elemento, con respecto al documento
     */
    xy: function (control) {
        if ( typeof control == "string")
            control = this.id(control)

        var r = control.getBoundingClientRect()

        return [r['left'] + window.pageXOffset, r['top'] + window.pageYOffset]
    },
}