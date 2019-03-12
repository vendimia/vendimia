V.Ajax = function(target) {
    this.payload = {}
    this.target = target || window.location.href
    this.method = 'POST'
    this.contentType = 'application/x-www-form-urlencoded'

    this.execute = function(method) {
        var XHR = new XMLHttpRequest()

        // Si payload no es un FormData, lo convertimos. Asumiermos que es un
        // objecto
        if (!(this.payload instanceof FormData) && this.method != 'GET') {
            // Convertimos el payload en un string
            var res = new FormData()
            for (var v in this.payload) {
                // Los arrays lo tratamos distinto
                if (this.payload[v] instanceof Array) {
                    for (d in this.payload[v]) {
                        res.append(v + '[]', this.payload[v][d]);
                    }
                } else {
                    res.append(v, this.payload[v]);
                }
            }
            this.payload = res
        }

        var target = this.target
        if (this.method == 'GET') {
            target += '?' + payload
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
        /*elements = V.id(formName).elements
        for (i = 0; i < elements.length; i++) {
            element = elements[i]

            // Usamos .checked de los checkboxes
            if (element.type.toLowerCase() == 'checkbox') {
                value = element.checked ? "1" : ""
            } else {
                value = element.value
            }

            this.payload[element.name] = value
        }*/
        this.payload = new FormData(V.id(formName))

        return this
    }

    this.post = function(payload)
    {
        this.payload = payload
        return this.execute('POST')
    }
}
