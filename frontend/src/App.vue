<template>
  <div id="app">
    <h1>Spaniel Generator 5000</h1>
    <h2>Prompt text</h2>
    <textarea v-model="internalText" readonly></textarea>
    <p>
      Raw text extracted from <strong>XRB AI Prompt_Rules and Examples.docx</strong>: 
      <button @click="downloadOriginal" class="download-btn">Download</button>
    </p>
    <form @submit.prevent="submitForm">
      <input type="file" @change="onFileChange" accept=".txt,.docx,.pdf" required>
      <br>
      <button type="submit" :disabled="loading || !selectedFile">
        Inflict text and file upon Gemini API
      </button>
      <span v-if="loading" class="loading-indicator">
        <span class="spinner"></span>
        Headbutting Gemini API...
      </span>
    </form>
    <div>
      <h2>Response</h2>
      <button @click="loadSample">Populate with sample JSON</button>
    </div>
    <div v-if="history.length" class="history-section">
      <h2>
        Past Responses
        <button @click="clearHistory" class="clear-btn">Clear all</button>
      </h2>
      <ul class="history-list">
        <li
          v-for="entry in pagedHistory"
          :key="entry.id"
          class="history-entry"
          @click="loadHistoryEntry(entry)"
        >
          <span class="history-filename">{{ entry.filename }}</span>
          <span class="history-timestamp">{{ entry.timestamp }}</span>
        </li>
      </ul>
      <div class="history-pagination">
        <button @click="historyPage--" :disabled="historyPage === 1">&laquo; Prev</button>
        <span>Page {{ historyPage }} of {{ totalHistoryPages }}</span>
        <button @click="historyPage++" :disabled="historyPage === totalHistoryPages">Next &raquo;</button>
      </div>
    </div>

    <div v-if="rawJson">
      <h2>Raw JSON <button @click="wordWrap = !wordWrap" class="wrap-btn">{{ wordWrap ? 'Unwrap' : 'Wrap' }}</button><button @click="showRawJson = !showRawJson" class="wrap-btn">{{ showRawJson ? 'Hide' : 'Show' }}</button></h2>
      <pre v-if="showRawJson" class="raw-json" :class="{ 'word-wrap': wordWrap }">{{ rawJson }}</pre>
    </div>
    <div v-if="formattedData.length">
      <h2>Formatted Output</h2>
      <div class="accordion">
        <div v-for="(section, index) in formattedData" :key="index" class="accordion-item">
          <button class="accordion-button" @click="toggleAccordion(index)">
            {{ section.title }}
          </button>
          <div class="accordion-content" :class="{ active: activeIndex === index }">
            <p v-for="(para, i) in section.paragraphs" :key="i">
              <strong v-if="para.number">{{ para.number }}</strong> {{ para.text }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'App',
  data() {
    return {
      internalText: '',
      selectedFile: null,
      rawJson: '',
      formattedData: [],
      activeIndex: null,
      wordWrap: false,
      loading: false,
      showRawJson: true,
      history: [],
      historyPage: 1,
      historyPageSize: 5
    }
  },
  mounted() {
    this.fetchInternalText()
    this.loadHistory()
  },
  computed: {
    totalHistoryPages() {
      return Math.max(1, Math.ceil(this.history.length / this.historyPageSize))
    },
    pagedHistory() {
      const start = (this.historyPage - 1) * this.historyPageSize
      return this.history.slice(start, start + this.historyPageSize)
    }
  },
  methods: {
    async fetchInternalText() {
      try {
        const response = await axios.get('/api/internal')
        this.internalText = response.data.text
      } catch (error) {
        console.error(error)
      }
    },
    onFileChange(event) {
      this.selectedFile = event.target.files[0]
    },
    async submitForm() {
      const formData = new FormData()
      formData.append('file', this.selectedFile)
      this.loading = true
      try {
        const response = await axios.post('/api/process', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        // Assume response.data is the JSON from Gemini
        const fencedJson = response.data.candidates[0].content.parts[0].text

        const match = fencedJson.match(/```(?:json)?\s*([\s\S]*?)```/)

        const apiData = match ? JSON.parse(match[1].trim()) : JSON.parse(fencedJson) 

        this.rawJson = JSON.stringify(apiData, null, 2)

        this.formattedData = apiData.sections || []
        this.saveToHistory(this.selectedFile.name, apiData)

      } catch (error) {
        console.error(error)
      } finally {
        this.loading = false
      }
    },
    loadHistory() {
      try {
        this.history = JSON.parse(localStorage.getItem('xrb_history') || '[]')
      } catch {
        this.history = []
      }
    },
    saveToHistory(filename, data) {
      const entry = {
        id: Date.now(),
        timestamp: new Date().toLocaleString(),
        filename,
        data
      }
      this.history.unshift(entry)
      localStorage.setItem('xrb_history', JSON.stringify(this.history))
      this.historyPage = 1
    },
    loadHistoryEntry(entry) {
      this.rawJson = JSON.stringify(entry.data, null, 2)
      this.formattedData = entry.data.sections || []
      this.activeIndex = null
    },
    clearHistory() {
      this.history = []
      localStorage.removeItem('xrb_history')
      this.historyPage = 1
    },
    toggleAccordion(index) {
      this.activeIndex = this.activeIndex === index ? null : index
    },
    async loadSample() {
      try {
        const response = await axios.get('/api/sample')
        this.rawJson = JSON.stringify(response.data, null, 2)
        this.formattedData = response.data.sections || []
      } catch (error) {
        console.error('Failed to load sample:', error)
      }
    },
    async downloadOriginal() {
      try {
        const response = await axios.get('/api/download', { responseType: 'blob' })
        const url = window.URL.createObjectURL(response.data)
        const link = document.createElement('a')
        link.href = url
        link.download = 'XRB AI Prompt_Rules and Examples.docx'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
      } catch (error) {
        console.error('Download failed:', error)
      }
    }
  }
}
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
  margin: 60px;
}

textarea {
  width: 100%;
  height: 200px;
  margin-bottom: 10px;
}

.download-btn {
  display: inline-block;
  margin-bottom: 20px;
  font-size: 0.875rem;
  cursor: pointer;
}

.raw-json {
  max-height: 400px;
  overflow: auto;
  text-align: left;
  background: #f8f8f8;
  border: 1px solid #ddd;
  padding: 10px;
  border-radius: 4px;
  font-size: 0.8rem;
  border: 1px solid #ccc;
  margin-bottom: 5px;
  white-space: pre;
}

.raw-json.word-wrap {
  white-space: pre-wrap;
}

.wrap-btn {
  font-size: 0.75rem;
  cursor: pointer;
  vertical-align: middle;
  margin-left: 8px;
}

.loading-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-left: 10px;
  font-size: 0.875rem;
  color: #666;
}

.spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid #ccc;
  border-top-color: #2c3e50;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.history-section {
  margin-bottom: 20px;
}

.history-list {
  list-style: none;
  padding: 0;
  margin: 8px 0;
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
}

.history-entry {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  cursor: pointer;
  background: #fff;
  border-bottom: 1px solid #eee;
  font-size: 0.875rem;
  transition: background 0.15s;
}

.history-entry:last-child {
  border-bottom: none;
}

.history-entry:hover {
  background: #f0f4f8;
}

.history-filename {
  font-weight: 600;
  color: #2c3e50;
}

.history-timestamp {
  color: #999;
  font-size: 0.8rem;
}

.history-pagination {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.875rem;
  margin-top: 6px;
}

.history-pagination button {
  cursor: pointer;
  padding: 2px 8px;
}

.history-pagination button:disabled {
  opacity: 0.4;
  cursor: default;
}

.clear-btn {
  font-size: 0.75rem;
  cursor: pointer;
  margin-left: 10px;
  vertical-align: middle;
  color: #c0392b;
}

.accordion-button {
  background: #f1f1f1;
  border: none;
  width: 100%;
  text-align: left;
  padding: 10px;
  cursor: pointer;
}

.accordion-content {
  display: none;
  padding: 10px;
}

.accordion-content.active {
  display: block;
}
</style>