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
            XHR.setRequestHeader('X-Vendimia-Requested-With', 'XmlHttpRequest');
            XHR.setRequestHeader('X-Vendimia-Security-Token',
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
     * Adds an object payload
     */
    this.appendPayload = function(payload)
    {
        for (var [name, value] of payload) {

            // Los arrays lo tratamos distinto
            if (payload[name] instanceof Array) {
                for (d in payload[name]) {
                    this.payload.append(name + '[]', payload[name][d]);
                }
            } else {
                this.payload.append(name, value);
            }
        }
        return this
    }

    this.post = function(payload)
    {
        this.appendPayload(payload)
        return this.execute('POST')
    }
}
