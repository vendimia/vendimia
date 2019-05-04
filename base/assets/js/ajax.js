V.Ajax = function(target) {
    this.payload = new FormData()
    this.target = target || window.location.href
    this.method = 'POST'
    this.contentType = 'application/x-www-form-urlencoded'

    this.execute = function(method) {
        var XHR = new XMLHttpRequest()

        var target = this.target
        if (this.method == 'GET') {
            target += '?' + new URLSearchParams(this.payload).toString()
            this.payload = null
        }

        // Nos fijamos si necesitamos añadir el URLBASE
        if (!/^.*:\/\//.test(target)) {
            target = V.URLBASE + target
        }

        return new Promise((resolve, reject) => {

            XHR.open(this.method, target)
            XHR.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            XHR.setRequestHeader('X-Csrf-Token',
                V.e("meta[name=vendimia-security-token]").content);
            //XHR.setRequestHeader('Content-Type', this.contentType);

            XHR.onreadystatechange = () => {
                if (XHR.readyState === XMLHttpRequest.DONE) {
                    if (XHR.status !== 200) {
                        reject(XHR.statusText, XHR.status)
                        return false
                    }
                    try {
                        payback = JSON.parse(XHR.responseText)
                    } catch (e) {
                        console.log (XHR.responseText)
                        reject(e.message)
                        return false
                    }
                    resolve(payback)
                }
            }
            XHR.send(this.payload)
        })
    }

    /**
     * Creates a payload from a form
     */
    this.fromForm = function(formName)
    {
        this.payload = new FormData(V.id(formName))
        return this
    }

    /**
     * Appends an element to the payload, changing the arrays to the PHP
     * accepted format.
     */
    this.appendPayloadElement = function(name, value)
    {
        // Los arrays lo tratamos distinto
        if (value instanceof Array) {
            for (d in value) {
                this.payload.append(name + '[]', value[d]);
            }
        } else {
            this.payload.append(name, value);
        }
    }

    /**
     * Adds an object or iterator to the payload
     */
    this.appendPayload = function(payload)
    {
        // Iteramos los iterables
        if (typeof payload[Symbol.iterator] === 'function') {
            for (var [name, value] of payload) {
                this.appendPayloadElement(name, value)
            }
        } else {
            // Recorremos los no iterables
            for (var name in payload) {
                this.appendPayloadElement(name, payload[name])
            }
        }
        return this
    }

    this.post = function(payload)
    {
        if (payload) {
            this.appendPayload(payload)
        }
        return this.execute('POST')
    }
}
