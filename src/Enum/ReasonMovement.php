<?php
namespace App\Enum;

enum reasonMovement:string 
{
    case SALE = 'sale';
    case LOSS = 'loss';
    case DOMAGED = 'domaged';
    case EXPIRED = 'expired';
    case SAMPLE = 'sample';
    case DEFECTIVE = 'defective';
    case TRANSFER_OUT = 'transfer_out';
    case RETURN_TO_SUPPLIER = 'return_to_supplier';
    case PURCHASE = 'purchase';
    case RETURN_CUSTOMER = 'return_customer';
    case TRANSFER_IN = 'transfer_in';
    case PRODUCTION_IN = 'production_in';

}

