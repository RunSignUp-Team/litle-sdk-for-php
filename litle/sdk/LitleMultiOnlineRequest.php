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
require_once realpath(dirname(__FILE__)) . '/LitleOnlineRequest.php';
require_once realpath(dirname(__FILE__)) . '/LitleCurlResponse.php';
class LitleMultiOnlineRequest extends LitleOnlineRequest
{
    /** Pending requests */
    protected $pendingRequests = array();

    public function saleRequest($hash_in)
    {
        $hash_out = array(
            'litleTxnId' => XmlFields::returnArrayValue($hash_in,'litleTxnId'),
            'orderId' =>Checker::requiredField(XmlFields::returnArrayValue($hash_in,'orderId')),
            'amount' =>Checker::requiredField(XmlFields::returnArrayValue($hash_in,'amount')),
            'surchargeAmount' =>XmlFields::returnArrayValue($hash_in,'surchargeAmount'),
            'orderSource'=>Checker::requiredField(XmlFields::returnArrayValue($hash_in,'orderSource')),
            'customerInfo'=>XmlFields::customerInfo(XmlFields::returnArrayValue($hash_in,'customerInfo')),
            'billToAddress'=>XmlFields::contact(XmlFields::returnArrayValue($hash_in,'billToAddress')),
            'shipToAddress'=>XmlFields::contact(XmlFields::returnArrayValue($hash_in,'shipToAddress')),
            'card'=> XmlFields::cardType(XmlFields::returnArrayValue($hash_in,'card')),
            'paypal'=>XmlFields::payPal(XmlFields::returnArrayValue($hash_in,'paypal')),
            'token'=>XmlFields::cardTokenType(XmlFields::returnArrayValue($hash_in,'token')),
            'paypage'=>XmlFields::cardPaypageType(XmlFields::returnArrayValue($hash_in,'paypage')),
            'mpos'=>(XmlFields::mposType(XmlFields::returnArrayValue($hash_in,'mpos'))),
            'billMeLaterRequest'=>XmlFields::billMeLaterRequest(XmlFields::returnArrayValue($hash_in,'billMeLaterRequest')),
            'fraudCheck'=>XmlFields::fraudCheckType(XmlFields::returnArrayValue($hash_in,'fraudCheck')),
            'cardholderAuthentication'=>XmlFields::fraudCheckType(XmlFields::returnArrayValue($hash_in,'cardholderAuthentication')),
            'customBilling'=>XmlFields::customBilling(XmlFields::returnArrayValue($hash_in,'customBilling')),
            'taxBilling'=>XmlFields::taxBilling(XmlFields::returnArrayValue($hash_in,'taxBilling')),
            'enhancedData'=>XmlFields::enhancedData(XmlFields::returnArrayValue($hash_in,'enhancedData')),
            'processingInstructions'=>XmlFields::processingInstructions(XmlFields::returnArrayValue($hash_in,'processingInstructions')),
            'pos'=>XmlFields::pos(XmlFields::returnArrayValue($hash_in,'pos')),
            'payPalOrderComplete'=> XmlFields::returnArrayValue($hash_in,'paypalOrderComplete'),
            'payPalNotes'=> XmlFields::returnArrayValue($hash_in,'paypalNotesType'),
            'amexAggregatorData'=>XmlFields::amexAggregatorData(XmlFields::returnArrayValue($hash_in,'amexAggregatorData')),
            'allowPartialAuth'=>XmlFields::returnArrayValue($hash_in,'allowPartialAuth'),
            'healthcareIIAS'=>XmlFields::healthcareIIAS(XmlFields::returnArrayValue($hash_in,'healthcareIIAS')),
            'filtering'=>XmlFields::filteringType(XmlFields::returnArrayValue($hash_in,'filtering')),
            'merchantData'=>XmlFields::merchantData(XmlFields::returnArrayValue($hash_in,'merchantData')),
            'recyclingRequest'=>XmlFields::recyclingRequestType(XmlFields::returnArrayValue($hash_in,'recyclingRequest')),
            'fraudFilterOverride'=> XmlFields::returnArrayValue($hash_in,'fraudFilterOverride'),
            'recurringRequest'=>XmlFields::recurringRequestType(XmlFields::returnArrayValue($hash_in,'recurringRequest')),
            'litleInternalRecurringRequest'=>XmlFields::litleInternalRecurringRequestType(XmlFields::returnArrayValue($hash_in,'litleInternalRecurringRequest')),
            'debtRepayment'=>XmlFields::returnArrayValue($hash_in,'debtRepayment'),
            'advancedFraudChecks'=>XmlFields::advancedFraudChecksType(XmlFields::returnArrayValue($hash_in,'advancedFraudChecks')),
        );

        $choice_hash = array($hash_out['card'],$hash_out['paypal'],$hash_out['token'],$hash_out['paypage'],$hash_out['mpos']);
        $choice2_hash= array($hash_out['fraudCheck'],$hash_out['cardholderAuthentication']);
        return $this->queueRequest($hash_out,$hash_in,'sale',$choice_hash,$choice2_hash);
    }

    protected function queueRequest($hash_out, $hash_in, $type, $choice1 = null, $choice2 = null)
    {
        $hash_config = LitleOnlineRequest::overrideConfig($hash_in);
        $hash = LitleOnlineRequest::getOptionalAttributes($hash_in,$hash_out);
        Checker::choice($choice1);
        Checker::choice($choice2);
        $request = Obj2xml::toXml($hash,$hash_config, $type);
        $rtn = new LitleCurlResponse();
        $this->pendingRequests[] = array(&$request,&$hash_config, $rtn);
        return $rtn;
    }
    
    public function processRequests()
    {
        $this->newXML->requests($this->pendingRequests,$this->useSimpleXml);
        $this->pendingRequests = array();
    }

}
