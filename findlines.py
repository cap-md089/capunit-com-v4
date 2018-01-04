from __future__ import with_statement

import os

for root, subFolders, files in os.walk('.') :
	for file in files :
		with open (os.path.join(root, file), 'r') as f :
			text = f.readlines()
			if len(text) > 0 and (text[0][0] == '\n' or text[0][0] == ' ') :
				print os.path.join(root, file)