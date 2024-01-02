<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;


class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $keyword = $request->date;
        $orders = Order::with('user')->where('created_at', 'LIKE', '%' . $keyword . '%')->simplePaginate(10);
        return view('order.kasir.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $medicines = Medicine::all();
        return view("order.kasir.create", compact('medicines'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_customer' => 'required',
            'medicines'=> 'required',
        ]);

        //mencari jumlah item yang sama pada array, strukturnya:
        // ["item"=> "jumlah"]

        $arrayDistinct = array_count_values($request->medicines);
        //menyiapkan array kosong untuk menampung format array baru

        $arrayAssocMedicines = [];
        //looping hasil perhitungan item distinct (duplikat)
        // key akan berupa value dari input medicine (id), item array berupa jumlah perhutungan item duplikat

        foreach($arrayDistinct as $id => $count){
            //mencari data obat berdasarkan id (obat yang di pilih)
            $medicine = Medicine::where('id', $id)->first();
            //ambil bagian coulm price daru gasul pencarian lalu kalikan dengan jumlah item duplikat sehingga,
            //akan menghasilkan tota harga dari pembelian tersebut
            $subPrice = $medicine['price'] * $count;
            //struktur value colum medicines menjadi multidimensi dengan dimensi kedua berbentuk array assos dengan key 
            //"id", "name_medicine", "price"


            if ($medicine['stock'] <= $count){
                return redirect()->back()->with('failed', 'stock habis!');
            }else{
                $getStock= $medicine->stock - $count;
                Medicine :: where ('id', $id)-> update([
                   'stock'=> $getStock
                ]);
            }
         

            $arrayItem = [
                "id" => $id,
                "name_medicine" => $medicine['name'],
                "qty" => $count,
                "price" => $medicine['price'],
                "sub_price" => $subPrice,
            ];
            //masukan struktur array tersebut ke array kosong yanv disediakan sebelumnya
            array_push($arrayAssocMedicines, $arrayItem);
        }
    


        //total harga pembelia dari obat-obat yang di pilih
        $totalPrice = 0;
        //looping format array medicines baru
        foreach($arrayAssocMedicines as $item){
        //total harga pembelian ditambahkan dari keseluruhan sub_price data medicines
            $totalPrice += (int) $item['sub_price'];
        }

    //harga beli di tambah 10% ppn
    $priceWithPPN = $totalPrice + ($totalPrice * 0.01);
    //tambah data ke database
    $proses = Order::create([
        //data user_id diambil dari id akun kasir yang sedang login
        'user_id'=> Auth::user()->id,
        'medicines'=>$arrayAssocMedicines,
        'name_customer'=>$request->name_customer,
        'total_price'=>$priceWithPPN,
    ]);

    if ($proses){
        //jika proses tamabh data berhasil, ambil data order yang dibuat oleh kasir yang sedang login (where), dengan tanggal paling terbaru
        //(orderBy), ambil hanya satu data(first)
        $order = Order::where('user_id', Auth::user()->id)->orderBy('created_at', 'DESC')->first();
        //kirim data order yang di ambl tadi, bagian column id sebagai parameter path dari route print

        return redirect()->route('kasir.order.print', $order['id']);
    } else {
        //jika tidak berhasil, maka diarahkan ke halaman dengan pesan pemberitahuan
        return redirect()->back()->with('failed', 'gagal membuat data pembelian. silahkan coba kembali dengan data yang sesuai!');
    }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show( $id )
    {
        $order = Order::find($id);
        return view('order.kasir.print', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */



    public function downloadPDF($id){
        $order = Order::find($id)->toArray();

        view()->share('order', $order);

        $pdf = PDF::loadView('order.kasir.download-pdf', $order);

        return $pdf->download('receipt.pdf');
    }




    public function data()
    {
      //with: mengambil data dari hasil relasi dan PK dan FK nya. 
      $orders = Order::with('user')->simplePaginate(5);
      return view("order.admin.index", compact('orders'));
    }

    public function exportExcel()
    {
        $file_name = 'data_pembelian'.'.xlsx';

        return Excel:: download(new OrdersExport, $file_name);
    }


    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
