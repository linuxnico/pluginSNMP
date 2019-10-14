
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

 $('#bt_cronGenerator').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
    });
});

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

//debut
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
//nom
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
    tr += '</td>';

//type
if (_cmd.name!='Rafraichir') {
    tr += '<td>';
    tr += '<span class="type hide" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
// oid
    if (_cmd.name!='Presence') {
      tr += '<td>';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration"  data-l2key="oid" style="width : 200px;" placeholder="{{OID}}" title="{{OID}}" >';
      tr += '</td>';
    } else { tr += '<td></td>'; }
// config unite, max et min
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite"  style="width : 100px;" placeholder="{{Unité}}" title="{{Unité}}" >';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}} style="margin-top : 3px;"> ';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}} style="margin-top : 3px;">';
    tr += '</td>';
  } else {
  tr += '<td></td><td></td><td></td>';
  }
//etat
    tr += '<td>';
    tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized"/> {{Historiser}}<br/></span>';
    tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" /> {{Affichage}}<br/></span>';
    if (_cmd.name!='Rafraichir') {
    tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</span> ';
  }
    tr += '</td>';
    tr += '<td>';
//test
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td>';
//fin
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}
