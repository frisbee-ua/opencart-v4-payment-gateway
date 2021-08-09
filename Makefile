ARCHIVENAME = frisbee.ocmod.zip

build:
	mkdir -p upload
	cp -r ./admin/ upload/admin/
	cp -r ./catalog/ upload/catalog/
	zip -r "$(ARCHIVENAME)" ./upload/ install.xml
	rm -rf ./upload/
