			// 
			// Get the sharing site
			// 
			var sharingSites = [];
			$.each($(".fl-sharing li a.selected"), function(i, item){
				siteId = item.id.substring("share_".length);
				sharingSites.push(siteId);
			});
			
			if (sharingSites.length > 0)
			{
				urlParameters["sharing"] = sharingSites;
			}