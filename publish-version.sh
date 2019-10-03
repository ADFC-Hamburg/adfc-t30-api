#!/bin/bash

OLD_VERSION=$(jq -r '.version' composer.json)
LAST_OLD_VERSION_NUM=$(echo $OLD_VERSION |sed -e 's/^.*\.//')
LAST_NEW_VERSION_NUM=$((LAST_OLD_VERSION_NUM+1))
NEW_VERSION=$(echo $OLD_VERSION |sed -e "s/\.${LAST_OLD_VERSION_NUM}$/\.${LAST_NEW_VERSION_NUM}/")

DIFF=$(git diff origin/master)
if [ -n "${DIFF}" ] ; then
    echo "Bitte Repo aktualisieren (git pull /git push)"
    exit 1
fi


cd vendor/ADFC-Hamburg/flexapi
git fetch
DIFF=$(git diff origin/master)
if [ -n "${DIFF}" ] ; then
    echo "Bitte Flexapi from Repo aktualisieren (git pull/git push)"
    exit 1
fi

pwd
git tag v${OLD_VERSION}
git push origin v${OLD_VERSION}
jq ".version=\"$NEW_VERSION\"" <composer.json >composer.json.new
mv composer.json.new composer.json
git commit -m "published version $OLD_VERSION begining work for version $NEW_VERSION" composer.json
git push origin master
cd ../../..
pwd
jq ".require[\"adfc-hamburg/flexapi\"]=\"^${OLD_VERSION}\"" composer.json >composer.json.new

mv composer.json.new composer.json
git commit -m 'Set flexapi Version to ${OLD_VERSION}' composer.json

git tag v${OLD_VERSION}
git push origin v${OLD_VERSION}
jq ".version=\"${NEW_VERSION}\"" <composer.json >composer.json.new
jq ".require[\"adfc-hamburg/flexapi\"]=\"dev-master\"" composer.json.new >composer.json
rm composer.json.new
git commit -m "published version $OLD_VERSION begining work for version $NEW_VERSION" composer.json
git push origin master
