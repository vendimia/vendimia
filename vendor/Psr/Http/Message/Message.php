<?php
namespace Psr\Http\Message;

/**
 * Message implementation.
 *
 * {@inheritDoc}
 * @author Oliver Etchebarne
 */
class Message implements MessageInterface
{
    protected $protocolVersion;

    /** Message headers */
    protected $headers = [];

    /** Lowercase header variables for lookup */
    protected $headersKeymap = [];

    /** */
    protected $body = null;

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        return key_exists(strtolower($name), $this->headersKeymap);
    }

    public function getHeader($name)
    {
        $name = strtolower($name);
        if (key_exists($name, $this->headersKeymap)) {
            return $this->headers[$this->headersKeymap[$name]];
        } else {
            return [];
        }
    }

    public function getHeaderLine($name)
    {
        return join(',', $this->getHeader($name));
    }

    public function withHeader($name, $value)
    {
        $new = clone $this;
        $new->setHeader($name, $value);
        return $new;
    }

    public function withAddedHeader($name, $value)
    {
        $new = clone $this;
        $new->setAddedHeader($name, $value);
        return $new;
    }

    public function withoutHeader($name) {
        $new = clone $this;
        $new->unsetHeader($name);
        return $new;
    }

    public function getBody() {
        return $this->body;
    }

    public function withBody(StreamInterface $body) {
        $new = clone $this;
        $new->setBody($body);
        return $new;
    }

    // Mutable accesors.

    /**
     * @see self::withHeader()
     */
    public function setHeader($name, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        // Evitamos duplicados
        $this->unsetHeader($name);

        $this->headers[$name] = $value;
        $this->headersKeymap[strtolower($name)] = $name;

        return $this;
    }

    /**
     * @see self::withAddedHeader();
     */
    public function setAddedHeader($name, $value)
    {
        if (!$this->hasHeader($name)) {
            $this->headers[$name] = [];
            $this->headersKeymap[strtolower($name)] = $name;
        } else {
            $this->headers[$name][] = $value;
        }

        return $this;
    }

    /**
     * @see self::withoutHeader();
     */
    public function unsetHeader($name) {
        if ($this->hasHeader($name)) {
            unset (
                $this->headers[$this->headersKeymap[strtolower($name)]]
            );
        }
        return $this;
    }

    /**
     * @see self::withBody()
     */
    public function setBody(StreamInterface $body)
    {
        $this->body = $body;
        return $this;
    }


}
