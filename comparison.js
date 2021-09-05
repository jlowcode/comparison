/*! Fabrik */

define (["jquery", "fab/list-plugin"], function (jQuery, FbListPlugin) {
    var FbListComparison = new Class ({
        Extends:FbListPlugin,
        initialize: function (options) {
            this.parent(options);
        },
        createModal: function() {
            var modal = document.createElement('div');
            modal.setAttribute('class', 'comparison-modal');
            modal.setAttribute('id', 'comparisonModal');

            var modalContent = document.createElement('div');
            modalContent.setAttribute('class', 'comparison-modal-content');

            var modalHeader = document.createElement('div');
            modalHeader.setAttribute('class', 'comparison-modal-header');
            var buttonClose = document.createElement('span');
            buttonClose.setAttribute('class', 'comparison-modal-close');
            buttonClose.innerHTML = '&times;';
            var title = document.createElement('h2');
            title.innerHTML = 'Comparações';
            modalHeader.appendChild(buttonClose);
            modalHeader.appendChild(title);

            var modalBody = document.createElement('div');
            modalBody.setAttribute('class', 'comparison-modal-body');

            var div_autocomplete = document.createElement('div');
            div_autocomplete.setAttribute('id', 'comparison_autocomplete');
            var input = document.createElement('input');
            input.setAttribute('type', 'text');
            input.setAttribute('id', 'input_comparison_autocomplete');
            input.setAttribute('placeholder', 'Adicionar');
            div_autocomplete.appendChild(input);
            var table_comparison = document.createElement('div');
            table_comparison.setAttribute('id', 'div_table_comparison');
            table_comparison.setAttribute('class', 'table-responsive');
            table_comparison.setAttribute('style', 'overflow: auto');
            modalBody.appendChild(div_autocomplete);
            modalBody.appendChild(table_comparison);

            var modalFooter = document.createElement('div');
            modalFooter.setAttribute('class', 'comparison-modal-footer');

            modalContent.appendChild(modalHeader);
            modalContent.appendChild(modalBody);
            modalContent.appendChild(modalFooter);
            modal.appendChild(modalContent);

            var body = document.getElement("body");
            var head = document.getElement('head');
            var linkCss = document.createElement('link');
            linkCss.setAttribute('href', this.options.url + 'plugins/fabrik_list/comparison/modal/modal.css');
            linkCss.setAttribute('rel', 'stylesheet');

            body.appendChild(modal);
            head.appendChild(linkCss);
        },
        autocomplete: function(inp, arr) {
            var currentFocus, self = this;

            /*execute a function when someone writes in the text field:*/
            inp.addEventListener("input", function(e) {
                var a, b, i, val = this.value, main_column = self.options.main_column['name'];
                /*close any already open lists of autocompleted values*/
                closeAllLists();
                if (!val) { return false;}
                currentFocus = -1;

                /*create a DIV element that will contain the items (values):*/
                a = document.createElement("DIV");
                a.setAttribute("id", this.id + "autocomplete-list");
                a.setAttribute("class", "comparison_autocomplete-items");
                /*append the DIV element as a child of the autocomplete container:*/
                this.parentNode.appendChild(a);
                /*for each item in the array...*/
                for (i = 0; i < arr.length; i++) {
                    /*check if the item starts with the same letters as the text field value:*/
                    if (arr[i][main_column].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
                        /*create a DIV element for each matching element:*/
                        b = document.createElement("DIV");
                        /*make the matching letters bold:*/
                        b.innerHTML = "<strong>" + arr[i][main_column].substr(0, val.length) + "</strong>";
                        b.innerHTML += arr[i][main_column].substr(val.length);
                        /*insert a input field that will hold the current array item's value:*/
                        b.innerHTML += "<input type='hidden' value='" + arr[i][main_column] + "'>";
                        b.innerHTML += "<input type='hidden' name='id_value' value='" + arr[i]['id'] + "'>";
                        /*execute a function when someone clicks on the item value (DIV element):*/
                        b.addEventListener("click", function(e) {
                            /*insert the value for the autocomplete text field:*/
                            inp.value = this.getElementsByTagName("input")[0].value;
                            var id_value = this.getElementsByTagName("input")[1].value;
                            self.addColumn(id_value);
                            /*close the list of autocompleted values,
                            (or any other open lists of autocompleted values:*/
                            closeAllLists();
                        });
                        a.appendChild(b);
                    }
                }
            });

            /*execute a function presses a key on the keyboard:*/
            inp.addEventListener("keydown", function(e) {
                var x = document.getElementById(this.id + "autocomplete-list");
                if (x) x = x.getElementsByTagName("div");
                if (e.keyCode == 40) {
                    /*If the arrow DOWN key is pressed,
                    increase the currentFocus variable:*/
                    currentFocus++;
                    /*and and make the current item more visible:*/
                    addActive(x);
                } else if (e.keyCode == 38) { //up
                    /*If the arrow UP key is pressed,
                    decrease the currentFocus variable:*/
                    currentFocus--;
                    /*and and make the current item more visible:*/
                    addActive(x);
                } else if (e.keyCode == 13) {
                    /*If the ENTER key is pressed, prevent the form from being submitted,*/
                    e.preventDefault();
                    if (currentFocus > -1) {
                        /*and simulate a click on the "active" item:*/
                        if (x) x[currentFocus].click();
                    }
                }
            });

            function addActive(x) {
                /*a function to classify an item as "active":*/
                if (!x) return false;
                /*start by removing the "active" class on all items:*/
                removeActive(x);
                if (currentFocus >= x.length) currentFocus = 0;
                if (currentFocus < 0) currentFocus = (x.length - 1);
                /*add class "autocomplete-active":*/
                x[currentFocus].classList.add("comparison_autocomplete-active");
            }

            function removeActive(x) {
                /*a function to remove the "active" class from all autocomplete items:*/
                for (var i = 0; i < x.length; i++) {
                    x[i].classList.remove("comparison_autocomplete-active");
                }
            }

            function closeAllLists(elmnt) {
                /*close all autocomplete lists in the document,
                except the one passed as an argument:*/
                var x = document.getElementsByClassName("comparison_autocomplete-items");
                for (var i = 0; i < x.length; i++) {
                    if (elmnt != x[i] && elmnt != inp) {
                        x[i].parentNode.removeChild(x[i]);
                    }
                }
            }

            /*execute a function when someone clicks in the document:*/
            document.addEventListener("click", function (e) {
                closeAllLists(e.target);
            });
        },
        watchButton: function() {
            if (typeOf(this.options.name) === 'null') {
                return;
            }

            this.createModal();
            this.insertLightBox();

            document.addEvent('click:relay(input[name^=checkAll])', (e, element) => {
                var ok = true;
                document.getElements ('input[name^=ids]').each (function (c) {
                    if (!c.checked) {
                        ok = false;
                    }
                });
                var row = e.target.getParent();
                var chx = row.getElement('input[name^=checkAll]');
                if (!ok) {
                    element.set("checked", true);
                    document.getElements ('input[name^=ids]').set ('checked', true);
                }
                else {
                    element.set('checked', false);
                    document.getElements ('input[name=checkAll]').set ('checked', false);
                    document.getElements ('input[name^=ids]').set ('checked', false);
                }
            });

            document.addEvent('click:relay(.' + this.options.name + ')', (e) => {
                if (e.rightClick) {
                    return;
                }
                e.stop();
                e.preventDefault();
                var row, chx, selected_ids = [];
                // if the row button is clicked check its associated checkbox
                if (e.target.getParent ('.fabrik_row')) {
                    row = e.target.getParent ('.fabrik_row');
                    if (row.getElement ('input[name^=ids]')) {
                        chx = row.getElement ('input[name^=ids]');
                        document.getElements ('input[name^=ids]').set ('checked', false);
                        chx.set ('checked', true);
                        jQuery("input[name^=checkAll]").prop("checked", false);
                    }
                }
                var ok = false;
                document.getElements ('input[name^=ids]').each (function (c) {
                    if (c.checked) {
                        ok = true;
                        selected_ids.push(c.value);
                    }
                });
                if (!ok) {
                    alert (Joomla.JText._ ('COM_FABRIK_PLEASE_SELECT_A_ROW'));
                    return;
                }
                else {
                    this.buttonAction(selected_ids);
                }
            });
        },
        createTable: function () {
            var columns = this.options.columns;

            var div = document.getElementById('div_table_comparison');
            var table = document.createElement('table');
            table.setAttribute('class', 'table');
            table.setAttribute('id', 'table_comparison')
            var thead = document.createElement('thead');
            var tr_head = document.createElement('tr');
            tr_head.setAttribute('id', 'tr_delete');
            var first_th = document.createElement('th');
            first_th.setAttribute('scope', 'col');
            first_th.innerHTML = '#';

            tr_head.appendChild(first_th);
            thead.appendChild(tr_head);
            table.appendChild(thead);

            var tbody = document.createElement('tbody');
            var i=0, tr, th;
            for (i=0; i<columns.length; i++) {
                tr = document.createElement('tr');
                tr.setAttribute('id', columns[i].name);
                th = document.createElement('th');
                th.setAttribute('scope', 'row');
                th.innerHTML = columns[i].label;
                tr.appendChild(th);
                tbody.appendChild(tr);
            }

            table.appendChild(tbody);
            div.appendChild(table);
        },
        getIndex: function (array, id) {
            var i, index;
            for (i=0; i<array.length; i++) {
                if (array[i]['id'] == id) {
                    index = i;
                }
            }
            return index;
        },
        addColumn: function (rowId) {
            var columns = this.options.columns;
            var index = this.getIndex(this.options.data_autocomplete, rowId);
            var data = this.options.data_autocomplete[index];
            var i=0;
            var tr, td;
            for (i=0; i<columns.length; i++) {
                tr = document.getElementById(columns[i].name);
                td = document.createElement('td');
                td.setAttribute('class', 'row_'+ rowId);
                td.innerHTML = data[columns[i].name];
                tr.appendChild(td);
            }
            var tr_head = document.getElementById('tr_delete');
            var td_head = document.createElement('td');
            td_head.setAttribute('class', 'row_' + rowId);
            var self = this;
            var a_delete = document.createElement('a');
            a_delete.setAttribute('style', 'cursor: pointer');
            a_delete.onclick = function () {
                jQuery('.row_' + rowId).remove();
                self.options.data_autocomplete.push(data);
                index = self.getIndex(self.options.data_used, rowId);
                self.options.data_used.splice(index, 1);
                self.autocomplete(document.getElementById('input_comparison_autocomplete'), self.options.data_autocomplete);
            };
            a_delete.innerHTML = 'x';
            td_head.appendChild(a_delete);
            tr_head.appendChild(td_head);

            this.options.data_used.push(data);
            index = this.getIndex(this.options.data_autocomplete, rowId);
            this.options.data_autocomplete.splice(index, 1);

            this.autocomplete(document.getElementById('input_comparison_autocomplete'), this.options.data_autocomplete);
        },
        openModal: function () {
            var modal = document.getElementById("comparisonModal");
            var body = document.getElement("body");

            body.style.overflowY = "hidden";
            modal.style.display = "block";

            var span = document.getElementsByClassName("comparison-modal-close")[0];

            span.onclick = function() {
                modal.style.display = "none";
                body.style.overflowY = "auto";
            };

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                    body.style.overflowY = "auto";
                }
            };
        },
        buttonAction: function (selected_ids) {
            if (jQuery("#comparisonModal").length) {
                jQuery("#table_comparison").remove();
                jQuery("#input_autocomplete").remove();
            }
            else {
                this.createModal();
            }
            this.createTable();

            this.openModal();

            var i;
            if ((this.options.data_used) && (this.options.data_autocomplete)) {
                for (i = 0; i < this.options.data_used.length; i++) {
                    this.options.data_autocomplete.push(this.options.data_used[i]);
                }
            }
            this.options.data_autocomplete = this.options.data;
            this.options.data_used = [];

            for (i=0; i<selected_ids.length; i++) {
                this.addColumn(selected_ids[i]);
            }
        },
        insertLightBox: function () {
            var existsCss = false, existsScript = false, a, b;

            var head = document.getElementsByTagName('head')[0],
                li = document.getElementsByTagName('link'),
                sc = document.getElementsByTagName('script');

            var path_css = this.options.url + 'plugins/fabrik_list/comparison/lib/lightbox/css/lightbox.css',
                path_js = this.options.url + 'plugins/fabrik_list/comparison/lib/lightbox/js/lightbox.js';

            for (var i=0; i<li.length; i++) {
                if (li[i].getAttribute('href') === path_css) {
                    existsCss = true;
                }
            }
            for (i=0; i<sc.length; i++) {
                if (sc[i].getAttribute('src') === path_js) {
                    existsScript = true;
                }
            }

            if (!existsCss) {
                a = document.createElement('link');
                a.setAttribute('href', path_css);
                a.setAttribute('rel', 'stylesheet');
                head.appendChild(a);
            }
            if (!existsScript) {
                b = document.createElement('script');
                b.setAttribute('src', path_js);
                head.appendChild(b);
            }

        }
    });

    return FbListComparison;
});





