
'''
CREATE TABLE boards (
    id integer NOT NULL,
    "order" smallint,
    name character varying(75) DEFAULT ''::character varying NOT NULL,
    type smallint DEFAULT (0)::smallint NOT NULL,
    start integer NOT NULL,
    uploadtype smallint,
    "desc" character varying(75) DEFAULT ''::character varying NOT NULL,
    image character varying(255) NOT NULL,
    section smallint DEFAULT (0)::smallint NOT NULL,
    maximagesize integer DEFAULT 1024000 NOT NULL,
    maxpages integer DEFAULT 11 NOT NULL,
    maxage integer DEFAULT 0 NOT NULL,
    markpage smallint DEFAULT (9)::smallint NOT NULL,
    maxreplies integer DEFAULT 200 NOT NULL,
    messagelength integer DEFAULT 8192 NOT NULL,
    createdon integer NOT NULL,
    locked smallint DEFAULT (0)::smallint NOT NULL,
    includeheader text DEFAULT ''::text NOT NULL,
    redirecttothread smallint DEFAULT (0)::smallint NOT NULL,
    anonymous character varying(255) DEFAULT 'Anonymous'::character varying NOT NULL,
    forcedanon smallint DEFAULT (0)::smallint NOT NULL,
    embeds_allowed character varying(255) DEFAULT ''::character varying NOT NULL,
    trial smallint DEFAULT (0)::smallint NOT NULL,
    popular smallint DEFAULT (0)::smallint NOT NULL,
    defaultstyle character varying(50) DEFAULT ''::character varying NOT NULL,
    locale character varying(30) DEFAULT ''::character varying NOT NULL,
    showid smallint DEFAULT (0)::smallint NOT NULL,
    compactlist smallint DEFAULT (0)::smallint NOT NULL,
    enablereporting smallint DEFAULT (1)::smallint NOT NULL,
    enablecaptcha smallint DEFAULT (0)::smallint NOT NULL,
    enablenofile smallint DEFAULT (0)::smallint NOT NULL,
    enablearchiving smallint DEFAULT (0)::smallint NOT NULL,
    enablecatalog smallint DEFAULT (1)::smallint NOT NULL,
    loadbalanceurl character varying(255) DEFAULT ''::character varying NOT NULL,
    loadbalancepassword character varying(255) DEFAULT ''::character varying NOT NULL,
    maxfiles smallint DEFAULT 1,
    newsage smallint DEFAULT 0
);
'''

import sys, os, re
from string import lower
REG_CAST = re.compile(r'\((.+?)\)::[a-z0-9\-]+')
REG_STR_CAST = re.compile(r'(\'.*?\')::[a-z0-9\-]+')
# FOREIGN KEY(player_ckey, player_slot) REFERENCES players(player_ckey, player_slot) ON DELETE CASCADE
REG_FK = re.compile(r'FOREIGN KEY\((?P<local_keys>[^\)]+)\) REFERENCES (?P<foreign_table>[a-zA-Z`]+)\((?P<foreign_keys>[^\)]+)\)')
REG_UNIQUE = re.compile(r'UNIQUE\((?P<indices>[^\)]+)\)')

PostgresTypeTranslations = {
    'character varying': 'string',
	'varchar': 'string',
    
	'character': 'char',
	'char':'char',
    
	'text': 'text',
	'json': 'text',
    
	'int': 'integer',
	'int4':'integer',
	'integer':'integer',
    'smallint':'integer',

	'decimal': 'decimal',
	'numeric':'decimal',
    
	'bigint': 'biginteger',
	'int8':'biginteger',
    
	'real': 'float',
	'float4': 'float',

	'bytea': 'binary',

	'time': 'time',
	'timetz':'time',
	'time with time zone':'time',
	'time without time zone':'time',

	'date':'date',

	'timestamp': 'timestamp',
	'timestamptz':'timestamp',
	'timestamp with time zone':'timestamp',
	'timestamp without time zone':'timestamp',

	'bool': 'boolean',
	'boolean':'boolean'                     
}

TypeReplacements = {}
InvTypeReplacements = {}
for type in PostgresTypeTranslations.keys():
    if ' ' in type:
        TypeReplacements[type] = type.replace(' ', '-')
        InvTypeReplacements[type.replace(' ', '-')] = type

def DumpAsPHP(data):
    if data is None:
        return 'null'
    if isinstance(data, (str, float, int)):
        return repr(data)
    elif isinstance(data, bool):
        return 'true' if data else 'false'
    elif isinstance(data, list):
        return 'array({})'.format(', '.join((DumpAsPHP(x) for x in data)))
    elif isinstance(data, dict):
        o = []
        for k, v in data.items():
            o += ['{} => {}'.format(DumpAsPHP(k), DumpAsPHP(v))]
        return 'array({})'.format(', '.join(o))
    return '/* ???: ' + type(data) + ' */'

def cleanIdentifier(ident):
    return ident.strip('"` ')

def toColumnList(data):
    return [cleanIdentifier(x) for x in data.split(',')]

class Column(object):
    def __init__(self, name):
        self.name = cleanIdentifier(name)
        self.type = ''
        self.default = None
        self.null = True
        self.unique = False
        self.limit = None
        self.unknown = []
        
    def setType(self, typename):
        global PostgresTypeTranslations, InvTypeReplacements
        
        typename = typename.lower()
        
        if typename.endswith(')'):
            typename, limit = typename.split('(')
            self.limit = int(limit[:-1])
            
        if typename in InvTypeReplacements:
            typename = InvTypeReplacements[typename]
            
        if typename in PostgresTypeTranslations:
            typename = PostgresTypeTranslations[typename]
        self.type = typename
        
    def __str__(self):
        opts = {}
        if self.default is not None: 
            opts['default'] = self.default
        if self.limit is not None:
            opts['limit'] = self.limit
        if self.unique:
            opts['unique'] = self.unique
        opts['null'] = self.null
        o = "\t->addColumn('{name}', '{type}', {options})".format(name=self.name, type=self.type, options=DumpAsPHP(opts))
        if len(self.unknown) > 0:
            o += '// Unknown blocks: {}'.format(' '.join(self.unknown))
        return o + '\n'

class ForeignKey(object):
    def __init__(self):
        self.delete = ''
        self.local_keys = []
        self.foreign_keys = []
        self.foreign_table = []
        self.delete = None
        self.update = None
        self.unknown = []
        
    def setType(self, typename):
        global PostgresTypeTranslations, InvTypeReplacements
        if typename.endswith(')'):
            typename, limit = typename.split('(')
            self.limit = int(limit[:-1])
            
        if typename in InvTypeReplacements:
            typename = InvTypeReplacements[typename]
            
        if typename in PostgresTypeTranslations:
            typename = PostgresTypeTranslations[typename]
        self.type = typename
        
    def __str__(self):
        opts = {}
        if self.delete is not None: 
            opts['delete'] = self.delete
        if self.update is not None:
            opts['update'] = self.update
        o = "\t->addForeignKey({local_keys}, '{foreign_table}', {foreign_keys}, {options})".format(local_keys=DumpAsPHP(self.local_keys), foreign_table=self.foreign_table, foreign_keys=DumpAsPHP(self.foreign_keys), options=DumpAsPHP(opts))
        if len(self.unknown) > 0:
            o += '// Unknown blocks: {}'.format(' '.join(self.unknown))
        return o + '\n'
    
class TableStatement(object):
    def __init__(self, name):
        self.id = None
        self.name = cleanIdentifier(name)
        self.unknown = []
        
    def setID(self, name):
        self.id = cleanIdentifier(name)
        
    def __str__(self):
        opts = {}
        if self.id != 'id' and self.id is not None: 
            opts['id'] = self.id
        o = "$this->table('{}'".format(self.name)
        if len(opts) > 0:
            o += ', ' + DumpAsPHP(opts)
        o += ')'
        if self.id == 'id': 
            o += ' // id is PK, auto-added by Phinx.'
        elif self.id is None:
            o += ' // XXX: UNRECOGNIZED PK'
        if len(self.unknown) > 0:
            o += ' // Unknown blocks: {}'.format(' '.join(self.unknown))
        
        return o + '\n'

with open(sys.argv[1], 'r') as inp:
    with open(sys.argv[1] + '.fixed', 'w') as out:
        waitingForPK = False
        inTable = False
        table = None
        for line in inp:
            line = line.strip()
            if line == '': continue;
            for oldtype, newtype in TypeReplacements.items():
                line = line.replace(oldtype, newtype)
            if not inTable:
                if line.startswith('CREATE TABLE'):
                    lc = line.split(' ')
                    table = TableStatement(lc[2])
                    waitingForPK = True
                    inTable = True
                    print(table.name)
                    continue
            if inTable:
                if line == ');':
                    out.write('\t->create();\n\n')
                    inTable = False
                elif line.startswith('UNIQUE'):
                    print('  ' + line)
                    if line.endswith(','):
                        line = line[:-1]
                    m = REG_UNIQUE.match(line)
                    uniqueColumns = toColumnList(m.group('indices'))
                    # ->addIndex(array('username', 'email'), array('unique' => true))
                    out.write('\t->addIndex({}, array(\'unique\' => true))\n'.format(DumpAsPHP(uniqueColumns)))
                    
                elif line.startswith('FOREIGN KEY'):
                    print('  ' + line)
                    if line.endswith(','):
                        line = line[:-1]
                    # FOREIGN KEY(player_ckey, player_slot) REFERENCES players(player_ckey, player_slot) ON DELETE CASCADE
                    m = REG_FK.match(line)
                    key = ForeignKey()
                    key.local_keys = toColumnList(m.group('local_keys'))
                    key.foreign_keys = toColumnList(m.group('foreign_keys'))
                    key.foreign_table = m.group('foreign_table').strip('`"')
                    skip = 1
                    inColumnList = False
                    for i in range(len(chunks)):
                        if skip > 0:
                            skip -= 1
                            continue
                        chunk = chunks[i]
                        if inColumnList:
                            if chunk.endswith(')'): 
                                inColumnList = False
                            continue
                        if chunk == 'ON' or chunk == 'REFERENCES' or chunk == 'FOREIGN':
                            continue
                        
                        if chunk.startswith('KEY(' + key.local_keys[0] + ',') \
                        or chunk.startswith(key.foreign_table + '(' + key.foreign_keys[0]):
                            if not chunk.endswith(')'):
                                inColumnList = True
                            continue
                        
                        elif chunk == 'DELETE':
                            key.delete = chunks[i + 1]
                            skip += 1
                            
                        elif chunk == 'UPDATE':
                            key.update = chunks[i + 1]
                            skip += 1
                            
                        else:
                            key.unknown += [chunk]
                    out.write(str(key))
                else:
                    print('  ' + line)
                    if line.endswith(','):
                        line = line[:-1]
                    line = REG_CAST.sub(r'\1', line)
                    line = REG_STR_CAST.sub(r'\1', line)
                    chunks = filter(len, line.split(' '))
                    column = None
                    skip = 0
                    for i in range(len(chunks)):
                        if skip > 0:
                            skip -= 1
                            continue
                        chunk = chunks[i]
                        if i == 0:  # Name
                            chunk = chunk.strip('"')
                            column = Column(chunk)
                        elif i == 1:
                            column.setType(chunk)
                        elif chunk == 'DEFAULT':
                            column.default = chunks[i + 1]
                            skip += 1
                        elif chunk == 'NOT':
                            column.null = False
                            skip += 1
                        elif chunk == 'UNIQUE':
                            column.unique = True
                        else:
                            column.unknown += [chunk]
                    skipLine=False
                    if waitingForPK:
                        if column.type.upper()=='INTEGER' and 'PRIMARY' in column.unknown and 'KEY' in column.unknown:
                            column.unknown[:] = [x for x in column.unknown if x not in ('PRIMARY','KEY','AUTOINCREMENT')] 
                            chunks = line.split(' ')
                            table.setID(chunks[0])
                            skipLine = True
                        out.write(str(table))
                        table = None
                        waitingForPK=False
                    if not skipLine:
                        out.write(str(column))
