from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError, RFCError, RFCLibError