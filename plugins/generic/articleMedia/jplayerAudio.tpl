{**
 * plugins/generic/articleMedia/jplayerAudio.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Template for jplayer audio player
 *}

<div id="jquery_jplayer_{$mediaId}" class="jp-jplayer"></div>
<div id="jp_container_{$mediaId}" class="jp-audio">
	<div class="jp-type-single">
		<div class="jp-gui jp-interface">
			<ul class="jp-controls">
				<li><a href="javascript:;" class="jp-play" tabindex="1">{translate key='plugins.generic.articleMedia.jplayer.play'}</a></li>
				<li><a href="javascript:;" class="jp-pause" tabindex="1">{translate key='plugins.generic.articleMedia.jplayer.pause'}</a></li>
				<li><a href="javascript:;" class="jp-stop" tabindex="1">{translate key='plugins.generic.articleMedia.jplayer.stop'}</a></li>
				<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">{translate key='plugins.generic.articleMedia.jplayer.mute'}</a></li>
				<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">{translate key='plugins.generic.articleMedia.jplayer.unmute'}</a></li>
				<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">{translate key='plugins.generic.articleMedia.jplayer.maxVolume'}</a></li>
			</ul>
			<div class="jp-progress">
				<div class="jp-seek-bar">
					<div class="jp-play-bar"></div>
				</div>
			</div>
			<div class="jp-volume-bar">
				<div class="jp-volume-bar-value"></div>
			</div>
			<div class="jp-time-holder">
				<div class="jp-current-time"></div>
				<div class="jp-duration"></div>

				<ul class="jp-toggles">
					<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">{translate key='plugins.generic.articleMedia.jplayer.repeat'}</a></li>
					<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">{translate key='plugins.generic.articleMedia.jplayer.repeatOff'}</a></li>
				</ul>
			</div>
		</div>
		<div class="jp-no-solution">
			{translate key='plugins.generic.articleMedia.jplayer.update'}
		</div>
	</div>
</div>
