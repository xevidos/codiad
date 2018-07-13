<!--
    Copyright (c) Codiad & Andr3as, distributed
    as-is and without warranty under the MIT License. 
    See http://opensource.org/licenses/MIT for more information.
    This information must remain intact.
-->
<form class="settings prefixer-settings">
    <label><span class="icon-traffic-cone big-icon"></span> AutoPrefixer options</label>
    <hr>
    <table>
         <tr>
            <td><label>Supported browsers</label></td>
            <td>
                <input type="text" class="setting" data-setting="codiad.plugin.prefixer.browsers" value="> 1%, last 2 versions, Firefox ESR, Opera 12.1">
            </td>
        </tr>
        <tr>
            <td><label>Visual Cascade</label></td>
            <td>
                <select class="setting" data-setting="codiad.plugin.prefixer.cascade">
                    <option value="true">True</option>
                    <option value="false">False</option>
                </select>
        </tr>
    </table>
    <p>Check <a href="https://github.com/postcss/autoprefixer#browsers">Github.com/Postcss/Autoprefixer</a> for more details.</p>
</form>