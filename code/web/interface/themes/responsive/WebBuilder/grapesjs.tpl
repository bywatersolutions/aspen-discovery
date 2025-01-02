<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="ISO-8859-1">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grapes JS Page Editor</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/grapesjs@0.21.10/dist/css/grapes.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/grapes.min.js" integrity="sha512-TavCuu5P1hn5roGNJSursS0xC7ex1qhRcbAG90OJYf5QEc4C/gQfFH/0MKSzkAFil/UBCTJCe/zmW5Ei091zvA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>
  <script src="https://unpkg.com/grapesjs-script-editor"></script>
  <script src="https://unpkg.com/grapesjs-plugin-forms"></script>
  <script src="https://unpkg.com/grapesjs-preset-webpage@1.0.2"></script>
  <script src="https://unpkg.com/grapesjs-tabs@1.0.6"></script>
  <script src="https://unpkg.com/grapesjs-custom-code@1.0.1"></script>
  <script src="https://unpkg.com/grapesjs-parser-postcss@1.0.1"></script>
  <script src="https://unpkg.com/grapesjs-tooltip@0.1.7"></script>
  <script src="https://unpkg.com/grapesjs-tui-image-editor@0.1.3"></script>
  <script src="https://unpkg.com/grapesjs-typed@1.0.5"></script>
  <script src="https://unpkg.com/grapesjs-style-bg@2.0.1"></script>

</head>
<body>
  <div id="gjs"></div>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    const templateId = urlParams.get('templateId'); 
    const grapesPageId = urlParams.get('id');
      const editor = grapesjs.init({
        container: "#gjs",
        fromElement: true,
        showOffsets: 1,
        noticeOnUnload: 0,
        storageManager: {
          type: 'remote',
          stepsBeforeSave: 1,
          contentTypeJson: true,
          storeComponents: true,
          storeStyles: true,
          storeHtml: true,
          storeCss: true,
          headers: { 'Content-Type': 'application/json' },
        },
        plugins: [
          'gjs-blocks-basic', 
          'grapesjs-script-editor',
          'grapesjs-plugin-forms',
          'grapesjs-preset-webpage',
          'grapesjs-tabs',
          'grapesjs-custom-code',
          'grapesjs-parser-postcss',
          'grapesjs-tooltip',
          'grapesjs-tui-image-editor',
          'grapesjs-style-bg',
          'grapesjs-typed',
        ],
        pluginsOpts: {
          'gjs-blocks-basic': {},
          'grapesjs-script-editor': {},
          'grapesjs-plugin-forms': {},
          'grapesjs-preset-webpage': {},
          'grapesjs-tabs': {},
          'grapesjs-custom-code': {},
          'grapesjs-parser-postcss': {},
          'grapesjs-tooltip': {},
          'grapesjs-tui-image-editor': {},
          'grapesjs-style-bg': {},
          'grapesjs-typed': {}
        },
      });
      // Add a save button - save as page
      editor.Panels.addButton('options', [{
        id: 'save-as-page',
        className: 'fas fa-save',
        command: 'save-as-page',
        attributes: { title: 'Save as Page' }
      }]);
      editor.Commands.add('save-as-page', {
        run: function (editor, sender) {
                sender && sender.set('active', 0);
                let projectData = editor.getProjectData();
                let grapesGenId = projectData.pages[0].id;
                let html = editor.getHtml();
                let css = editor.getCss();
                let pageData = {
                    templateId: templateId,
                    grapesPageId: grapesPageId,
                    grapesGenId: grapesGenId,
                    projectData: projectData,
                    html: html,
                    css: css,
                };  
                var url = Globals.path + '/WebBuilder/AJAX?method=saveAsPage';
                $.ajax({
                    url: url,
                    type: "POST",
                    dataType: "json",
                    data: JSON.stringify({
                        "templateId": templateId,
                        "grapesPageId": grapesPageId,
                        "grapesGenId": grapesGenId,
                        "projectData": projectData,
                        "html": html,
                        "css": css,
                    }),
                    contentType: "application/json",
                    success: function (response) {
                      if (response.success) {
                        AspenDiscovery.showMessage('Success', response.message);
                      } else {
                        AspenDiscovery.showMessage('Error', response.message || 'Failed to save page.');
                      }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error saving page: ', error);
                        let errorMessage = 'Failed to save page. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message){
                          errorMessage = xhr.responseJSON.message;
                        }
                        AspenDiscovery.showMessage('Error', errorMessage);
                    }
                });
            }
      });
      editor.getWrapper().set('stylable', true);

      //Add headers in Headers block
      editor.BlockManager.add('h1', {
          label: 'H1',
          content: '<h1>Heading 1</h1>',
          category: 'Headers',
          attributes: { class: 'fa fa-header' }
      });
      editor.BlockManager.add('h2', {
          label: 'H2',
          content: '<h2>Heading 2</h2>',
          category: 'Headers',
          attributes: { class: 'fa fa-header' },
      });
      editor.BlockManager.add('h3', {
          label: 'H3',
          content: '<h3>Heading 3</h3>',
          category: 'Headers',
          attributes: { class: 'fa fa-header' },
      });

      //Add tooltips for buttons that do not include them by default
      const buttonsWithTooltips = [
            { id: 'undo', title: 'Undo Last Action' },
            { id: 'redo', title: 'Redo Last Action' },
            { id: 'gjs-open-import-webpage', title: 'Add Custom Code' },
            { id: 'canvas-clear', title: 'Clear Canvas' }
        ];

        buttonsWithTooltips.forEach(button => {
          const buttonElement = editor.Panels.getButton('options', button.id);
          if (buttonElement) {
              buttonElement.set('attributes', {
                  title: button.title,
                  'data-tooltips-pos': 'bottom'
              });
          }
        });

      editor.on('load', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const templateId = urlParams.get('templateId'); 
        const grapesPageId = urlParams.get('id');
        var url = Globals.path + '/WebBuilder/AJAX?method=loadGrapesPage&id=' + grapesPageId + '&templateId=' + templateId;
        $.ajax({
          url: url,
          type: 'GET',
          success: function(data) {
          try {
            if (data.success) {
              editor.setComponents(data.html);
              editor.setStyle(data.css);
              editor.loadProjectData(data.projectData);
            } else {
              console.log('Error Loading Page:', data.message);
            }
          } catch (e) {
            console.error("Failed to parse JSON response:", e);
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.error("AJAX call failed:", textStatus, errorThrown);
        }
      });
    });
    
  </script>
  <style>
    .gjs-editor-cont {
      z-index: 1;
      position: relative;
    }

    .fa-save {
      background-color: #3174AF;
      border: 1px solid #3174AF;
    }

    .gjs-block-label {
      font-size: 10px;
    }
  </style>
</body>
</html>