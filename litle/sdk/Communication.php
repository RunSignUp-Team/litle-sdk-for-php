<?php
/*
 * Copyright (c) 2011 Litle & Co.
*
* Permission is hereby granted, free of charge, to any person
* obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without
* restriction, including without limitation the rights to use,
* copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the
* Software is furnished to do so, subject to the following
* conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
* FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
* OTHER DEALINGS IN THE SOFTWARE.
*/
namespace litle\sdk;
class Communication
{
    public static function httpRequest($req,$hash_config=NULL)
    {
        $config = Obj2xml::getConfig($hash_config);

        if ((int) $config['print_xml']) {
            echo $req;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY, $config['proxy']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml'));
        curl_setopt($ch, CURLOPT_URL, $config['url']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($ch,CURLOPT_TIMEOUT, $config['timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        $output = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (! $output) {
            throw new \Exception (curl_error($ch));
        } else {
            curl_close($ch);
            if ((int) $config['print_xml']) {
                echo $output;
            }

            return $output;
        }

    }
    
    /**
     * Handle multiple HTTP requests
     *
     * @param array $requests Requests in form (XML request, hash config, LitleCurlResponse)
     */
    public static function httpRequests($requests)
    {
        // Multi curl init
        if (!($mh = curl_multi_init()))
            throw new \Exception ('Failed to create curl handle');
        $chs = array();
        
        $tmp = null;
        $resps = array();
        foreach ($requests as &$tmp)
        {
            $req = &$tmp[0];
            $hash_config = &$tmp[1];
            
            $config = Obj2xml::getConfig($hash_config);
            
            if ((int) $config['print_xml']) {
                echo $req;
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_PROXY, $config['proxy']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml'));
            curl_setopt($ch, CURLOPT_URL, $config['url']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt($ch,CURLOPT_TIMEOUT, $config['timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
            $chs[] = $ch;
            curl_multi_add_handle($mh, $ch);
            $resps[(int)$ch] = $tmp[2];
        }
        
        // Execute (Read/write data)
        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
            }
            else
                usleep(250);// Short sleep
            
            // Read/write data from handles
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            
            // Process data
            while (($data = curl_multi_info_read($mh)))
            {
                $idx = (int)$data['handle'];
                $resp = $resps[$idx];
                
                // Check for error
                if ($data['result'] !== CURLM_OK)
                {
                    $resp->error = curl_error($data['handle']);
                    $resp->errno = curl_errno($data['handle']);
                }
                else
                {
                    $resp->response = curl_multi_getcontent($data['handle']);
                    if (!$resp->response)
                    {
                        $resp->response = null;
                        $resp->error = curl_error($data['handle']);
                        $resp->errno = curl_errno($data['handle']);
                    }
                    else
                    {
                        if ((int) $config['print_xml'])
                            echo $output;
                    }
                }
            }
        }
        unset($tmp);
        
        // Close handles
        foreach ($chs as $ch)
        {
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }
}
