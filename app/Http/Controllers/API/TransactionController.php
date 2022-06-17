<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request){
        $id = $request->input('id');
        $limit = $request->input('limit',6);
        $status = $request->input('status');

        if($id){
            $transaction = Transaction::with(['items.product'])->find($id);

            if($transaction){
                return ResponseFormatter::success($transaction,'data transaction berhasil diambil');
            }else{
                return ResponseFormatter::error(null,'data transaction tidak ada',404);
            }

        }

        $transaction = Transaction::with(['items.product'])->where('users_id',Auth::user()->id);

        if($status){
            $transaction->where('status',$status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list transaction berhasil diambil'
        );
        
    }

    public function checkout(Request $request){
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exists:product,id', // di dalam array ada id, nah di cek table product kolum id adaga tuh
            'total_price'=> 'required',
            'shipping_price'=> 'required',
            'status'=> 'required|in:PENDING,SUCCESS,CANCELED,FAILED,SHIPPINNG,SHIPPED',
        ]);

        $transaction = Transaction::create([
            'users_id'=> Auth::user()->id,
            'address'=>$request->address,
            'total_price'=>$request->total_price,
            'shipping_price'=>$request->shipping_price,
            'status'=>$request->status,
        ]);

        foreach($request->items as $product){
            TransactionItem::create([
                'users_id'=> Auth::user()->id,
                'products_id'=>$product['id'],
                'transaction_id'=>$transaction->id,
                'quantity' => $product['quantity']

            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'), 'Transaction berhasil');
    }
}
