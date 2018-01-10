<?php
/**
 * Created by PhpStorm.
 * User: daidai
 * Date: 17/12/4
 * Time: 下午3:56
 */

namespace tlanyan;



class XmlParser
{

    /**
     * @var bool whether to throw a [[BadRequestHttpException]] if the body is invalid json
     */
    public $throwException = true;


    /**
     * {@inheritdoc}
     */
    public function parse($rawBody, $contentType)
    {
        if (preg_match('/charset=(.*)/i', $contentType, $matches)) {
            $encoding = $matches[1];
        } else {
            $encoding = 'UTF-8';
        }
        $dom = new \DOMDocument('1.0', $encoding);
        $dom->loadXML($rawBody, LIBXML_NOCDATA);
        return $this->convertXmlToArray(simplexml_import_dom($dom->documentElement));
    }

    /**
     * Converts XML document to array.
     * @param string|\SimpleXMLElement $xml xml to process.
     * @return array XML array representation.
     */
    protected function convertXmlToArray($xml)
    {
        if (is_string($xml)) {
            $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        $result = (array)$xml;
        foreach ($result as $key => $value) {
            if (!is_scalar($value)) {
                $result[$key] = $this->convertXmlToArray($value);
            }
        }
        return $result;
    }
}