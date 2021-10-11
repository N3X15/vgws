import argparse
import os
import re

from buildtools import log, os_utils

import tqdm

REG_NS = r'namespace ([\\A-Za-z]+);'
IN_DIR = os.path.join('classes')
OUT_DIR = os.path.join('classes')


def main():
    argp = argparse.ArgumentParser()
    argp.add_argument('--go', action='store_true')
    args = argp.parse_args()

    files_to_proc = []
    for root, _, files in os.walk(IN_DIR):
        for bfn in files:
            fullpath = os.path.abspath(os.path.join(root, bfn))
            if bfn.endswith('.bak'):
                log.info('rm %s', fullpath)
                os.remove(fullpath)
            if bfn.endswith('.php'):
                files_to_proc += [fullpath]

    for filename in tqdm.tqdm(files_to_proc, desc='Moving files...', unit='file'):
        namespace = None
        outpath = None
        with open(filename, 'r') as f:
            for line in f:
                m = re.match(REG_NS, line)
                if m is not None:
                    namespace = m.group(1)
                    break
        if namespace is None:
            continue
        nschunks = namespace.split('\\')
        if nschunks[0] == '':
            nschunks = nschunks[1:]
        nschunks = nschunks[1:]

        nschunks += [os.path.basename(filename).replace('.class', '').replace('.interface','')]
        outpath = os.path.abspath(os.path.join(OUT_DIR, *nschunks))
        if outpath == filename:
            continue

        cmd = [os_utils.which('git'), 'mv', os.path.relpath(filename), os.path.relpath(outpath)]
        if args.go:
            os_utils.ensureDirExists(os.path.dirname(outpath), noisy=True)
            os_utils.cmd([os_utils.which('git'), 'add', os.path.relpath(filename)], echo=True, show_output=True)
            os_utils.cmd(cmd, echo=True, critical=True)
        else:
            log.info(' '.join(cmd))
    os_utils.del_empty_dirs(IN_DIR, quiet=False)

if __name__ == '__main__':
    main()
