<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 14:34
 */

$xml = '<custom-attributes>
                <custom-attribute attribute-id="legal1">false</custom-attribute>
                <custom-attribute attribute-id="legal2">false</custom-attribute>
                <custom-attribute attribute-id="legal3">false</custom-attribute>
                <custom-attribute attribute-id="legalNewsletter">false</custom-attribute>
                <custom-attribute attribute-id="legalNewsletter2">false</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardAddress">-</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardCardType">TITOLARE</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardCity">-</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardCountry">-</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardCustomerType">04</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardFirstReward">Buono del valore di 10 Euro</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardLastName">LIASTRO                                           </custom-attribute>
                <custom-attribute attribute-id="loyaltyCardMissingPoints">500</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardName">LISA                                              </custom-attribute>
                <custom-attribute attribute-id="loyaltyCardNextRewardPoints">500</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardNumber">035976364</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardPhone">-</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardPointsBalance">0</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardState">-</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardWalletAmount">0.0</custom-attribute>
                <custom-attribute attribute-id="loyaltyCardZipCode">-</custom-attribute>
                <custom-attribute attribute-id="postalCode">42122</custom-attribute>
            </custom-attributes>';

$obj = new SimpleXMLElement($xml);
echo "\n".(string)$obj->{'custom-attribute'}[0]['attribute-id'];
echo "\n".(string)$obj->{'custom-attribute'}[0];
echo "\n";
print_r($obj);