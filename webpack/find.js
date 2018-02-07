
const fs   = require('fs')
const path = require('path')
const util = require('util')

const readDir = path => util.promisify(fs.readdir)(path)
const stats   = path => util.promisify(fs.stat)(path)

const makeIsFile = src => async file => (await stats(path.join(src, file))).isFile()

const getTypeEntries = async (prevTypeEntries, type) => {

    const typeDir = path.resolve(type, 'src')
    const typeFiles = await readDir(typeDir)
    const typeEntries = await Promise.all(typeFiles.filter(makeIsFile(typeDir)))
    const entries = await prevTypeEntries

    typeEntries.forEach(entry => entries[path.join(type, entry)] = path.join(typeDir, entry))

    return entries
}

module.exports = async (...types) => await types.reduce(getTypeEntries, {})
