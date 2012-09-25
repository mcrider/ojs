$(function() {
	window.mediaWizard = {
		initialize: function() {
			// Set up click handlers
			$('.testItem').live('click', this.testItem);
			$('.addItem').live('click', this.addItem);
			$('.addMetadataItem').live('click', this.addMetadataItem);
			$('.deleteItem').live('click', this.deleteItem);
			$('.deleteMetadata').live('click', this.deleteMetadata);
		},

		addItem: function(e) {
			// Copy value of last mediaItem and replace Id with new Id
			var template = $('.mediaItemTemplate').html();
			var lastId = parseInt($('#mediaItems').find('.mediaItem').last().find('.itemId').val(), 10) || 0;
			var newId = lastId + 1;

			// Use the correct media id
			var contents = template.replace(/--IDNUM--/g, newId);

			$("#mediaItems").append(contents);
			$('.mediaItems').find('.mediaItem').last().find('.itemId').val(newId);
			$('.mediaItems').find('.mediaItem').last().find('.sourceId').val('');
		},

		addMetadataItem: function(e) {
			// Copy value of last mediaItem and replace Id with new Id
			var template = $('.metadataItemTemplate').html();
			var mediaId = parseInt($(e.target).parent().parent().parent().parent().find('.itemId').val(), 10);

			// Use the correct media Id
			var contents = template.replace(/--IDNUM--/g, mediaId);

			$(e.target).parent().parent().find('.metadata').append(contents);
		},

		deleteItem: function(e) {
			$(e.target).parents('.mediaItem').remove();
		},

		deleteMetadata: function(e) {
			$(e.target).parents('.metadataItem').remove();
		},

		testItem: function(e) {
			// Disable the loading button
			$(e.target).val('Loading...').attr("disabled", "true");

			var source = $(e.target).parent().parent().find('.mediaSource').val();
			var sourceId = $(e.target).parent().parent().find('.sourceId').val();
			var id = $(e.target).parent().parent().find('.itemId').val();

			// TODO: Find media type of source and use in setMedia; return false if doesn't exist
			if(!source) {
				alert('Please enter a URL or video ID');
				return false;
			}

			if (source == 'youtube') {
				if (sourceId.indexOf("youtube") != -1) {
					// We've got a full URL, get the video ID
					var videoId = sourceId.split('v=')[1];
					var ampersandPosition = videoId.indexOf('&');
					if(ampersandPosition != -1) {
						videoId = videoId.substring(0, ampersandPosition);
					}
					sourceId = videoId;
				}
				$(e.target).parent().parent().parent().parent().find('.sourceId').val(sourceId);
				$(e.target).parent().parent().parent().parent().find('.mediaContainer').html('<iframe title="Youtube Video Player" width="320" height="240" src="http://www.youtube.com/embed/'+sourceId+'?fs=1&autoplay=1&loop=0" frameborder="0" allowfullscreen style="border: 1px solid black"></iframe>').show();
				$(e.target).parent().parent().parent().parent().find('.metadata').show();
				$.ajax({
					url: "http://gdata.youtube.com/feeds/api/videos/"+sourceId+"?v=2&alt=json",
					dataType: "jsonp",
					success: window.mediaWizard.parseYoutube(id)
				});
			} else if(source == 'openimages') {
				if (sourceId.indexOf("openimages") != -1) {
					// We've got a full URL, get the video ID
					var videoId = sourceId.replace(/\D+/, '');
					var slashPosition = videoId.indexOf('/');
					if(slashPosition != -1) {
						videoId = videoId.substring(0, slashPosition);
					}
					sourceId = videoId;
				}

				$(e.target).parent().parent().parent().parent().find('.metadata').show();
				$('#mediaItem_'+id).find('.audioPlayer').remove();
				$.ajax({
					type: "GET",
					url: window.parseXmlUrl,
					dataType: "xml",
					data: {url: "http://www.openimages.eu/feeds/oai/?verb=GetRecord&identifier=oai:openimages.eu:"+sourceId+"&metadataPrefix=oai_dc"},
					success: window.mediaWizard.parseOpenImages(id),
					error: function(xml) {
						alert('Could not load data from OpenImages');
					}
				});
			} else if(source == 'soundcloud') {
				if (sourceId.indexOf("soundcloud") != -1) {
					// We've got a full URL, get the video ID
					var videoId = sourceId.substr(sourceId.lastIndexOf('/') + 1);
					sourceId = videoId;
				}
				$(e.target).parent().parent().parent().parent().find('.sourceId').val(sourceId);
				$(e.target).parent().parent().parent().parent().find('.metadata').show();
				$.ajax({
					url: "http://api.soundcloud.com/tracks/"+sourceId+".json?client_id=2f509ea7a0cb87d0a85bf12f29454778",
					dataType: "jsonp",
					success: window.mediaWizard.parseSoundCloud(id)
				});
			} else if(source == 'euscreen') {
				if (sourceId.indexOf("euscreen") != -1) {
					// We've got a full URL, get the video ID
					var videoId = sourceId.split('id=')[1];
					var ampersandPosition = videoId.indexOf('&');
					if(ampersandPosition != -1) {
						videoId = videoId.substring(0, ampersandPosition);
					}
					sourceId = videoId;
				}

				$(e.target).parent().parent().parent().parent().find('.metadata').show();
				$('#mediaItem_'+id).find('.audioPlayer').remove();
				$.ajax({
					type: "GET",
					url: window.parseXmlUrl,
					dataType: "xml",
					data: {url: "http://euscreen.image.ece.ntua.gr/euscreen/Search?query="+sourceId},
					success: window.mediaWizard.parseEUScreen(id),
					error: function(xml) {
						alert('Could not load data from EUScreen');
					}
				});
			} else { // Direct link to audio/video file
				// Standard audio/video format, no API needed
				var formatAndType = window.mediaWizard.determineFormat(sourceId);
				var format = formatAndType.format;

				if(formatAndType.type == 'audio') {
					$('#mediaItem_'+id).find('.videoPlayer').remove();
					$('#mediaItem_'+id).find('.mediaType').val(2);
				} else {
					$('#mediaItem_'+id).find('.audioPlayer').remove();
				}
				window.mediaWizard.initJplayer(id, formatAndType.format);

				var mediaAttribs = {};
				mediaAttribs[format] = sourceId;

				$('#mediaItem_'+id).find('.jpContainer').show();
				$('#mediaItem_'+id).find('.metadata').show();
				$("#jquery_jplayer_"+id).jPlayer("setMedia", mediaAttribs).jPlayer('play');
				$('.testItem').val('Load').removeAttr('disabled');
			}
		},

		parseYoutube: function(mediaId) {
			return function(data) {
				// Display the metadata
				var metadata = [
					{'label': 'Title', 'value': data.entry.title.$t },
					{'label': 'Description', 'value': data.entry.media$group.media$description.$t },
					{'label': 'Views', 'value': data.entry.yt$statistics.viewCount },
					{'label': 'Uploaded by', 'value': data.entry.author[0].name.$t }
				];

				// Copy value of last mediaItem and replace Id with new Id
				var template = $('.metadataItemTemplate').html();
				// Use the correct media Id
				var contents = template.replace(/--IDNUM--/g, mediaId);

				$.each(metadata, function(key, value) {
					$metadataContainer = $('#mediaItem_'+mediaId).find('.metadata').append(contents);
					$metadataContainer.find('.metadataItem').last().find('.metadataLabel').val(value['label']);
					$metadataContainer.find('.metadataItem').last().find('.metadataValue').html(value['value']);
				});
				$('.testItem').val('Load').removeAttr('disabled');
			};
		},

		parseSoundCloud: function(mediaId) {
			return function(data) {
				// Display the metadata
				var metadata = [
					{'label': 'Title', 'value': data.title },
					{'label': 'Description', 'value': data.description },
					{'label': 'Created', 'value': data.created_at },
					{'label': 'Plays', 'value': data.playback_count },
					{'label': 'Genre', 'value': data.genre },
					{'label': 'User', 'value': data.user.username }
				];
				var soundCloudId = data.id;

				var embedCode = '<iframe width="100%" height="166" scrolling="no" frameborder="no" src="http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F'+soundCloudId+'&show_artwork=true"></iframe>';
				$("#mediaItem_"+mediaId).find('.mediaContainer').html(embedCode).show();

				// Copy value of last mediaItem and replace Id with new Id
				var template = $('.metadataItemTemplate').html();
				// Use the correct media Id
				var contents = template.replace(/--IDNUM--/g, mediaId);

				$.each(metadata, function(key, value) {
					$metadataContainer = $('#mediaItem_'+mediaId).find('.metadata').append(contents);
					$metadataContainer.find('.metadataItem').last().find('.metadataLabel').val(value['label']);
					$metadataContainer.find('.metadataItem').last().find('.metadataValue').html(value['value']);
				});
				$('.testItem').val('Load').removeAttr('disabled');
			};
		},

		parseOpenImages: function(mediaId) {
			return function(xmlString) {
				var format = false;
				var formatAndType = null;
				var source = null;
				$.each($(xmlString).find("format"), function(key, value) {
					source = $(value).text();
					formatAndType = window.mediaWizard.determineFormat(source);
					format = formatAndType.format;
					return !format; // If format is found, break out of the loop
				});
				if (!format) return false;
				// Load the video
				window.mediaWizard.initJplayer(mediaId, formatAndType.format);

				var mediaAttribs = {};
				mediaAttribs[format] = source;

				$("#mediaItem_"+mediaId).find('.jpContainer').show();
				$("#jquery_jplayer_"+mediaId).jPlayer("setMedia", mediaAttribs).jPlayer('play');

				// Display the metadata
				var metadata = [
					{'label': 'Title', 'value': $(xmlString).find("title").last().text() },
					{'label': 'Description', 'value': $(xmlString).find("description").last().text() },
					{'label': 'Creator', 'value': $(xmlString).find("creator").last().text() },
					{'label': 'Date', 'value': $(xmlString).find("date").last().text() },
					{'label': 'Type', 'value': $(xmlString).find("type").last().text() },
					{'label': 'Language', 'value': $(xmlString).find("language").last().text() },
					{'label': 'rights', 'value': $(xmlString).find("rights").last().text() }
				];

				// Copy value of last mediaItem and replace Id with new Id
				var template = $('.metadataItemTemplate').html();
				// Use the correct media Id
				var contents = template.replace(/--IDNUM--/g, mediaId);

				$.each(metadata, function(key, value) {
					$metadataContainer = $('#mediaItem_'+mediaId).find('.metadata').append(contents);
					$metadataContainer.find('.metadataItem').last().find('.metadataLabel').val(value['label']);
					$metadataContainer.find('.metadataItem').last().find('.metadataValue').html(value['value']);
				});
				$('.testItem').val('Load').removeAttr('disabled');
			};
		},

		parseEUScreen: function(mediaId) {
			return function(xmlString) {
				var format = 'mp4';
				var source = $(xmlString).find("field[name=/eus:AdministrativeMetadata/eus:filename_tg]").first().text();
				window.mediaWizard.initJplayer(mediaId, format);

				var mediaAttribs = {};
				mediaAttribs[format] = source;

				$("#mediaItem_"+mediaId).find('.jpContainer').show();
				$("#jquery_jplayer_"+mediaId).jPlayer("setMedia", mediaAttribs).jPlayer('play');

				// Display the metadata
				var metadata = [
					{'label': 'Title', 'value': $(xmlString).find("field[name=/eus:ContentDescriptiveMetadata/eus:TitleSet/eus:TitleSetInOriginalLanguage/eus:title_tg]").last().text() },
					{'label': 'Title in English', 'value': $(xmlString).find("field[name=/eus:ContentDescriptiveMetadata/eus:TitleSet/eus:TitleSetInEnglish/eus:title_tg]").last().text() },
					{'label': 'Series Title', 'value': $(xmlString).find("field[name=/eus:ContentDescriptiveMetadata/eus:TitleSet/eus:TitleSetInOriginalLanguage/eus:seriesTitle_tg]").last().text() },
					{'label': 'Series Title in English', 'value': $(xmlString).find("field[name=/eus:ContentDescriptiveMetadata/eus:TitleSet/eus:TitleSetInEnglish/eus:seriesTitle_tg]").last().text() },
					{'label': 'Provider', 'value': $(xmlString).find("field[name=/eus:AdministrativeMetadata/eus:provider_tg]").last().text() },
					{'label': 'Publisher / broadcaster', 'value': $(xmlString).find("field[name=/eus:AdministrativeMetadata/eus:publisherbroadcaster_tg]").last().text() },
					{'label': 'Production year', 'value': $(xmlString).find("field[name=/eus:ObjectDescriptiveMetadata/eus:SpatioTemporalInformation/eus:TemporalInformation/eus:productionYear_tg]").last().text() },
					{'label': 'Country of production', 'value': $(xmlString).find("field[name=/eus:ObjectDescriptiveMetadata/eus:SpatioTemporalInformation/eus:SpatialInformation/eus:CountryofProduction_tg]").last().text() },
					{'label': 'Genre', 'value': $(xmlString).find("field[name=/eus:ContentDescriptiveMetadata/eus:genre_tg]").last().text() },
					{'label': 'Topic', 'value': $(xmlString).find("field[name=/eus:ContentDescriptiveMetadata/eus:topic_tg]").last().text() }
				];

				// Copy value of last mediaItem and replace Id with new Id
				var template = $('.metadataItemTemplate').html();
				// Use the correct media Id
				var contents = template.replace(/--IDNUM--/g, mediaId);

				$.each(metadata, function(key, value) {
					$metadataContainer = $('#mediaItem_'+mediaId).find('.metadata').append(contents);
					$metadataContainer.find('.metadataItem').last().find('.metadataLabel').val(value['label']);
					$metadataContainer.find('.metadataItem').last().find('.metadataValue').html(value['value']);
				});
				$('.testItem').val('Load').removeAttr('disabled');
			};
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

	window.mediaWizard.initialize();
});