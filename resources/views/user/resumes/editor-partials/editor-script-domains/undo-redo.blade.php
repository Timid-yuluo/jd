        snapshotModules() {
            return JSON.parse(JSON.stringify(this.modules.map(m => ({ id: m.id ?? null, _key: m._key, type: m.type, data: m.data, sort_order: m.sort_order }))));
        },

        pushUndo() {
            _state.undoStack.push(this.snapshotModules());
            if (_state.undoStack.length > 50) _state.undoStack.shift();
            _state.redoStack = [];
        },

        undo() {
            if (_state.undoStack.length === 0) return;
            _state.redoStack.push(this.snapshotModules());
            const snapshot = _state.undoStack.pop();
            this.modules = snapshot.map((m, i) => ({ ...m, sort_order: i }));
            this.showToast('已撤销', 'info');
        },

        redo() {
            if (_state.redoStack.length === 0) return;
            _state.undoStack.push(this.snapshotModules());
            const snapshot = _state.redoStack.pop();
            this.modules = snapshot.map((m, i) => ({ ...m, sort_order: i }));
            this.showToast('已重做', 'info');
        },
