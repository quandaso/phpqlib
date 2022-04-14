<?php
namespace App\Controllers\{namespace};


use App\Controllers\Controller;
use App\Models\{modelName};
use Q\Request;
use Q\Exceptions\HttpNotFoundException;

class {controller} extends Controller
{
    /**
    * Index page
    * @param Request $request
    * @return \Q\Response\ViewResponse
    */
    public function index(Request $request)
    {

        $entries = {modelName}::orderBy('id', 'desc')->paginate();
        return view('{namespace}/{name}/index', compact('entries'));
    }

    /**
    * Create page
    * @param Request $request
    * @return \Q\Response\ViewResponse
    */
    public function create(Request $request)
    {
        if ($request->isPOST()) {
            $params = $request->params();
            unset($params['csrf_token']);
            $entry = new {modelName}();
            foreach($params as $k => $v) {
                $entry->$k = $v;
            }
            $entry->save();
            return redirect(url('{routePrefix}/index'));
        }

        return view('{namespace}/{name}/create');
    }

    /**
    * Edit entry
    * @param Request $request
    * @return \Q\Response\RedirectResponse|\Q\Response\ViewResponse
    * @throws HttpNotFoundException
    */
    public function edit(Request $request)
    {
        $id = $request->param('id');
        $entry =  {modelName}::find($id);
        if (!$entry) {
            throw new HttpNotFoundException('Entry not found');
        }

        if ($request->isPOST()) {
            $params = $request->params();
            unset($params['csrf_token']);

            foreach($params as $k => $v) {
                $entry->$k = $v;
            }

            $entry->save();
            return redirect(url('{routePrefix}/edit?id=' . $entry->id));
        }


        return view('{namespace}/{name}/edit', compact('entry'));
    }

    /**
    * Remove an entry
    * @param Request $request
    * @return \Q\Response\RedirectResponse
    * @throws HttpNotFoundException
    */
    public function remove(Request $request) {
        $id = $request->param('id');
        $entry =  {modelName}::find($id);
        if (!$entry) {
            throw new HttpNotFoundException('Entry not found');
        }

        $entry->delete();
        return redirect(url('{routePrefix}/index'));
    }
}