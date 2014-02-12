<h3>Relation Tags</h3>
<label for="digitalobjectlinkerplugin_thumb_tag">Thumbnail tag indicator (ex thumb:):</label>
<p><?php echo get_view()->formText('digitalobjectlinkerplugin_thumb_tag', 
                              get_option('digitalobjectlinkerplugin_thumb_tag'));?></p>
<label for="digitalobjectlinkerplugin_full_image_tag">Full image tag indicator (ex full:):</label>
<p><?php echo get_view()->formText('digitalobjectlinkerplugin_full_image_tag', 
                              get_option('digitalobjectlinkerplugin_full_image_tag'));?></p>
<h3>Admin Interface</h3>
<label for="digitalobjectlinkerplugin_embed_admin">Embed thumb in admin item show pages?</label>
<p><?php echo get_view()->formCheckbox('digitalobjectlinkerplugin_embed_admin', 
                                  true, 
                                  array('checked' => (boolean) get_option('digitalobjectlinkerplugin_embed_admin'))); ?></p>
<label for="digitalobjectlinkerplugin_width_admin">Image width, in pixels:</label>
<p><?php echo get_view()->formText('digitalobjectlinkerplugin_width_admin', 
                              get_option('digitalobjectlinkerplugin_width_admin'), 
                              array('size' => 5));?></p>
<h3>Public Theme</h3>
<label for="digitalobjectlinkerplugin_embed_public">Embed thumb in public item show pages?</label>
<p><?php echo get_view()->formCheckbox('digitalobjectlinkerplugin_embed_public', 
                                  true, 
                                  array('checked' => (boolean) get_option('digitalobjectlinkerplugin_embed_public'))); ?></p>
<label for="digitalobjectlinkerplugin_width_public">Image width, in pixels:</label>
<p><?php echo get_view()->formText('digitalobjectlinkerplugin_width_public', 
                              get_option('digitalobjectlinkerplugin_width_public'), 
                              array('size' => 5));?></p>

