{% extends 'base.tpl' %}

{% block scripts %}
{% if not timezone_set %}
<script type="text/javascript">
    $(document).ready(function() {
      var timezone = jstz.determine();
      $.ajax({
          type: "GET",
          url: "{{ baseurl }}/timezone.php",
          data: 'timezone='+ timezone.name(),
          success: function(){
              //location.reload();
          }
      });
    });
</script>
{% endif %}
{% endblock %}

{% block content %}
   <table id="page-table" >
   <tr>
   <td id="page-td">

     <div class="login-github">
       <a title="Please login with GitHub" href="login.php"></a>
     </div>

   </td>
   </tr>
   </table>
{% endblock %}