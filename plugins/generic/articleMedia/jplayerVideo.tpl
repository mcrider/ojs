{**
 * plugins/generic/articleMedia/jplayerVideo.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Template for a jplayer video player
 *}

<div id="jp_container_{$mediaId}" class="jp-video jp-video-270p">
	<div class="jp-type-single">
		<div id="jquery_jplayer_{$mediaId}" class="jp-jplayer"></div>
		<div class="jp-gui">
			<div class="jp-video-play">
				<a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
			</div>
			<div class="jp-interface">
				<div class="jp-progress">
					<div class="jp-seek-bar">
						<div class="jp-play-bar"></div>
					</div>
				</div>
				<div class="jp-current-time"></div>
				<div class="jp-duration"></div>
				<div class="jp-controls-holder">
					<ul class="jp-controls">
						<li><a href="javascript:;" class="jp-play" tabindex="1">{translate key='plugins.generic.articleMedia.jplayer.play'}</a></li>
						<li><a href="javascript:;" class="jp-pause" tabindex="1">{translate key='plugins.generic.articleMedia.jplayer.pause'}</a></li>
						<li><a href="javascript:;" class="jp-stop" tabindex="1">{translate key='plugins.generic.articleMedia.jplayer.stop'}</a></li>
						<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">{translate key='plugins.generic.articleMedia.jplayer.mute'}</a></li>
						<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">{translate key='plugins.generic.articleMedia.jplayer.unmute'}</a></li>
						<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">{translate key='plugins.generic.articleMedia.jplayer.maxVolume'}</a></li>
					</ul>
					<div class="jp-volume-bar">
						<div class="jp-volume-bar-value"></div>
					</div>
					<ul class="jp-toggles">
						<li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">{translate key='plugins.generic.articleMedia.jplayer.fullScreen'}</a></li>
						<li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">{translate key='plugins.generic.articleMedia.jplayer.restoreScreen'}</a></li>
						<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">{translate key='plugins.generic.articleMedia.jplayer.repeat'}</a></li>
						<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">{translate key='plugins.generic.articleMedia.jplayer.repeatOff'}</a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="jp-no-solution">
			<span>Update Required</span>
			To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
		</div>
	</div>
</div>
