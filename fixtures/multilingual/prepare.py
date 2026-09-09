"""Create an isolated, local-only editor fixture. Never modifies a source checkout."""
import argparse
import base64
import json
from pathlib import Path
import secrets
import shutil
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument('--major', choices=['4', '5'], required=True)
parser.add_argument('--destination', type=Path, required=True)
for name in ['core', 'filament', 'pro']:
    parser.add_argument('--'+name, type=Path, required=True)
args = parser.parse_args()
destination = args.destination.resolve()
if destination.exists():
    raise SystemExit('Destination must not exist; use a fresh directory for each package candidate.')
repositories = []
sources = {}
for name, package, version in [('core', 'laravel-seo', '3.20.0'), ('filament', 'laravel-seo-filament', '1.12.0'), ('pro', 'laravel-seo-pro', '2.40.1')]:
    source = getattr(args, name).resolve()
    if not (source/'composer.json').is_file():
        raise SystemExit(f'Missing source composer.json: {source}')
    repositories.append({'type':'path', 'url':str(source), 'options':{'symlink':False, 'versions':{'rankbeam/'+package:version}}})
    sources[name] = {'path':str(source), 'commit':subprocess.check_output(['git','-C',str(source),'rev-parse','HEAD'],text=True).strip(), 'dirty':bool(subprocess.check_output(['git','-C',str(source),'status','--porcelain'],text=True).strip())}
shutil.copytree(Path(__file__).parent/'app', destination)
composer = {
    'name':'rankbeam/multilingual-editor-fixture', 'type':'project', 'license':'MIT',
    'require': {'php':'^8.4', 'laravel/framework':'^12.0', 'filament/filament':'4.13.1' if args.major=='4' else '5.8.1', 'lara-zeus/spatie-translatable':'1.0.4' if args.major=='4' else '2.0.1', 'rankbeam/laravel-seo':'3.20.0', 'rankbeam/laravel-seo-filament':'1.12.0', 'rankbeam/laravel-seo-pro':'2.40.1'},
    'autoload':{'psr-4':{'App\\':'app/', 'Database\\Seeders\\':'database/seeders/'}},
    'require-dev':{'spatie/laravel-sitemap':'^7.0', 'phpunit/phpunit':'^11.5', 'mockery/mockery':'^1.6'},
    'repositories': repositories,
    'config':{'sort-packages':True, 'allow-plugins':{'php-http/discovery':True}},
    'minimum-stability':'stable', 'prefer-stable':True,
}
(destination/'composer.json').write_text(json.dumps(composer,indent=2)+'\n',encoding='utf-8')
(destination/'.rankbeam-multilingual-fixture').write_text(json.dumps({'filament_major':args.major, 'sources':sources},indent=2)+'\n',encoding='utf-8')
port='824'+args.major
environment={'APP_NAME':'Rankbeam editor fixture', 'APP_ENV':'local', 'APP_DEBUG':'false', 'APP_KEY':'base64:'+base64.b64encode(secrets.token_bytes(32)).decode(), 'APP_URL':'http://127.0.0.1:'+port, 'APP_LOCALE':'en','APP_FALLBACK_LOCALE':'en','DB_CONNECTION':'sqlite','DB_DATABASE':(destination/'database/database.sqlite').as_posix(),'SESSION_DRIVER':'file','CACHE_STORE':'array','QUEUE_CONNECTION':'sync','LOG_CHANNEL':'single','MAIL_MAILER':'array'}
(destination/'.env').write_text(''.join(f'{key}="{value}"\n' for key,value in environment.items()),encoding='utf-8')
(destination/'database/database.sqlite').touch()
print(json.dumps({'directory':str(destination),'url':environment['APP_URL'],'sources':sources},indent=2))
