<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::orderBy('created_at', 'desc')->get();
        return view("admin_panel.table.index", compact('tables'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_name' => 'required|unique:tables,table_name,' . $request->edit_id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        if ($request->has('edit_id') && $request->edit_id != '') {
            $table = Table::find($request->edit_id);
            $msg = [
                'success' => 'Table Updated Successfully',
                'reload' => true
            ];
        } else {
            $table = new Table();
            $msg = [
                'success' => 'Table Created Successfully',
                'reload' => true
            ];
        }

        $table->table_name = $request->table_name;
        $table->status = $request->status ?? 'available';
        $table->save();

        return response()->json($msg);
    }

    public function delete($id)
    {
        $table = Table::find($id);
        if ($table) {
            $table->delete();
            $msg = [
                'success' => 'Table Deleted Successfully',
                'reload' => route('table.index'),
            ];
        } else {
            $msg = ['error' => 'Table Not Found'];
        }
        return response()->json($msg);
    }
}
