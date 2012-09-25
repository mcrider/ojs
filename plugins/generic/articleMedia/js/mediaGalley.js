$(function() {
	window.mediaGalley = {
		initialize: function() {
			$('.hideMetadata').removeClass('open').addClass('close');
			$('.hideMetadata').next('.metadataDisplayContainer').hide(400);
			$('.open').live('click', function() {
				$(this).removeClass('open').addClass('close');
				$(this).next('.metadataDisplayContainer').hide(400);
			});
			$('.close').live('click', function() {
				$(this).removeClass('close').addClass('open');
				$(this).next('.metadataDisplayContainer').show(400);
			});
		},

		deleteItem: function(e) {
			$(e.target).parent().parent().remove();
		},

		loadMediaItem: function(id, source) {
			var format = window.mediaGalley.determineFormat(source);

			var mediaAttribs = {};
			mediaAttribs[format] = source;

			$("#jquery_jplayer_"+id).jPlayer("setMedia", mediaAttribs);
		},

		initJplayer: function(id, format) {
			$("#jquery_jplayer_"+id).jPlayer({
				ready: function () {
					
				},
				play: function() { // To avoid both jPlayers playing together.
					$(this).jPlayer("pauseOthers");
				},
				swfPath: "../js",
				supplied: format,
				cssSelectorAncestor: "#jp_container_" + id
			});
		},


		// Determine the format of a media URL
		determineFormat: function(url) {
			var fileName = url.substr(url.lastIndexOf("/") + 1, url.lastIndexOf("?") == -1 ? url.length : url.lastIndexOf("?"));

			var fileTypes = {'m4v': 'video',
							'ogv':  'video',
							'mp4':  'video',
							'm4a':  'audio',
							'mp3':  'audio'};

			var format = false;
			var type = 'video';
			$.each(fileTypes, function(index, value) {
				var regex = /(?:\.([^.]+))?$/;
				var extension = regex.exec(fileName)[1];

				if (index == extension) {
					format = index;
					type = value;
				}
			});

			if (!format) return false;
			return {'format': format, 'type': type};
		}
	};

	window.mediaGalley.initialize();
});