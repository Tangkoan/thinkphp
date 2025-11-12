define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'product/stock/index' + location.search,
                    add_url: 'product/stock/add',
                    edit_url: 'product/stock/edit',
                    del_url: 'product/stock/del',
                    details_url: 'product/stock/details',
                    multi_url: 'product/stock/multi',
                    import_url: 'product/stock/import',
                    table: 'stock',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'product.product_name', title: __('Product Name') },
                        { field: 'quantity', title: __('Quantity'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content },
                        { field: 'type', title: __('Type'), searchList: { "1": __('Stock In'), "2": __('Stock Out') }, formatter: Table.api.formatter.label },
                        { field: 'status', title: __('Status'), searchList: { "1": __('Public'), "0": __('Draft') }, formatter: Table.api.formatter.status },
                        { field: 'date', title: __('Date'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        { field: 'updatetime', title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            formatter: function (value, row, index) {

                                // ✅ បន្ថែមបន្ទាត់នេះសម្រាប់ DEBUG
                                // console.log("Current Row Data:", row);

                                // ប៊ូតុង Edit
                                var html = [];
                                html.push('<a href="javascript:;" class="btn btn-xs btn-success btn-editone" title="Edit"><i class="fa fa-pencil"></i></a>');
                                // ប៊ូតុង Delete
                                html.push('<a href="javascript:;" class="btn btn-xs btn-danger btn-delone" title="Delete"><i class="fa fa-trash"></i></a>');

                                // 🟦 ប៊ូតុង Details (កូដពីមុន)
                                var options = table.bootstrapTable('getOptions');
                                var detailsUrl = options.extend.details_url;
                                var pk = options.pk;
                                var id = row[pk]; // <--- បញ្ហាគឺនៅត្រង់នេះ (id ក្លាយជា undefined)

                                // console.log("Found ID:", id); // ✅ បន្ថែមបន្ទាត់នេះសម្រាប់ DEBUG

                                var fullDetailsUrl = detailsUrl + '/ids/' + id;

                                html.push('<a href="' + fullDetailsUrl + '" class="btn btn-xs btn-info btn-dialog" title="Details"><i class="fa fa-eye"></i></a>');

                                return html.join(' ');
                            }
                        }

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
