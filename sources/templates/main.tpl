{% extends 'base.tpl' %}

{% block content %}

<div class="container">

<p>Logged in as {{ user.name }} (<a href="{{ baseurl }}/logout.php">Log out</a>)<p>

<div class="panel-group" id="app-accordion">

{% for app in apps %}

{% if app.up_to_date %}
  <div class="panel panel-success">
{% else %}
  <div class="panel panel-danger">
{% endif %}

    <div class="panel-heading">
      <div class="panel-title">
        <a data-toggle="collapse" data-parent="#app-accordion" href="#app_{{ app.id }}">{{ app.name }} <em><small>({{ app.id }})</small></em></a>
        <div class="pull-right small">
          <a href="{{ app.diff_url }}" target="_blank">
            <small>{{ app.status }}</small>
          </a>
        </div>
      </div>
    </div>
    <div class="panel-collapse collapse" id="app_{{ app.id }}">
      <div class="panel-body">
        <p><strong>Description</strong>: {{ app.desc }}</p>
        <p><strong>Last update (UTC)</strong>: {{ app.update }}</p>
        <p><strong>Maintainer</strong>: {{ app.maintainer }} <small class="text-muted">({{ app.maintainer_mail }})</small></p>
        <p><strong>Git</strong>: {{ app.git }} <small class="text-muted">({{ app.branch }})</small></p>
        <p><strong>Published revision</strong>: {{ app.published_rev }}</p>
        <p><strong>Latest revision</strong>: {{ app.trunk_rev }}</p>
        <a href="{{ app.diff_url }}" target="_blank" class="btn btn-default">View diff</a>
      </div>
    </div>
    
  </div>

{% endfor %}

</div>

</div>

{% endblock %}