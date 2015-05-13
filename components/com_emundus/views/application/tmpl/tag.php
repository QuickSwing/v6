<?php
defined('_JEXEC') or die('Restricted access');
JFactory::getSession()->set('application_layout', 'tag');
?>

<style type="text/css">
    .widget .panel-body { padding:0px; }
    .widget .list-group { margin-bottom: 0; }
    .widget .panel-title { display:inline }
    .widget .label-info { float: right; }
    .widget li.list-group-item {border-radius: 0;border: 0;border-top: 1px solid #ddd;}
    .widget li.list-group-item:hover { background-color: rgba(86,61,124,.1); }
    .widget .mic-info { color: #666666;font-size: 14px; }
    .widget .action { margin-top:5px; display: inline;}
    .widget .comment-text { font-size: 12px; display: inline;}
    .widget .btn-block { border-top-left-radius:0px;border-top-right-radius:0px; }
</style>

<div class="tags">
    <div class="row">
        <div class="panel panel-default widget">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-tags"></span>
                <h3 class="panel-title"><?php echo JText::_('TAGS'); ?></h3>
                <span class="label label-info"><?php echo count($this->tags); ?></span>
            </div>
            <div class="panel-body">
                <ul class="list-group">
                    <?php
                    if(count($this->tags) > 0) {
                        $i=0;
                        foreach($this->tags as $tag){ ?>
                            <li class="list-group-item" id="<?php echo $tag['id_tag']; ?>" fnum="<?php echo $this->fnum; ?>">
                                <div class="row">
                                    <div class="col-xs-10 col-md-11">
                                        <div>

                                            <div class="mic-info">
                                                <a href="#"><?php echo $tag['name']; ?></a> - <?php echo JHtml::_('date', $tag['date_time'], JText::_('DATE_FORMAT_LC2')); ?>
                                            </div>
                                        </div>
                                        <div class="comment-text">
                                            <h1><span class="label <?php echo $tag['class']; ?>"><?php echo $tag['label']; ?></span>
                                                <?php if($this->_user->id == $tag['user_id'] || EmundusHelperAccess::asAccessAction(14, 'd', $this->_user->id, $this->fnum)):?>
                                                        <button type="button" class="btn btn-danger btn-xs" title="<?php echo JText::_('DELETE');?>">
                                                            <span class="glyphicon glyphicon-trash"></span>
                                                        </button>
                                                <?php endif; ?>
                                            </h1>
                                        </div>

                                    </div>
                                </div>
                            </li>
                            <?php
                            $i++;
                        }
                    } else echo JText::_('NO_TAG');
                    ?>
                </ul>
            </div>

            <?php if(EmundusHelperAccess::asAccessAction(14, 'c', $this->_user->id, $this->fnum)):?>
                <div class="form" id="form"></div>
            <?php endif;?>

        </div>
    </div>
</div>

<script type="text/javascript">
    //$(".tags .comment-text .btn.btn-danger.btn-xs").unbind("click");
    $(document).off('click', '.tags .comment-text .btn.btn-danger.btn-xs');
    $(document).on('click', '.tags .comment-text .btn.btn-danger.btn-xs', function(e)
    {
        if (e.handle === true) {
            e.handle = false;
            url = 'index.php?option=com_emundus&controller=application&task=deletetag';
            var id = $(this).parents('li').attr('id');
            var fnum = $(this).parents('li').attr('fnum');
            $.ajax({
                type:'GET',
                url:url,
                dataType:'json',
                data:({fnum:fnum, id_tag:id}),
                success: function(result)
                {
                    if(result.status)
                    {

                        $('.tags li[id='+id+']').empty();
                        $('.tags li[id='+id+']').append(result.msg);
                        var nbCom = parseInt($('.panel-default.widget .panel-heading .label.label-info').text().trim())
                        nbCom--;
                        $('.panel-default.widget .panel-heading .label.label-info').html(nbCom);
                    }
                    else
                    {
                        $('#form').append('<p class="text-danger"><strong>'+result.msg+'</strong></p>');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown)
                {
                    console.log(jqXHR.responseText);
                }
            });
        }
    });
</script>