<template>
  <div id="app">
    <h1>Accountancy Rules Processor</h1>
    <h2>Prompt text</h2>
    <textarea v-model="internalText" readonly></textarea>
    <p>
      Generated from XRB AI Prompt_Rules and Examples.docx:
      <button @click="downloadOriginal" class="download-btn">Download</button>
    </p>
    <form @submit.prevent="submitForm">
      <input type="file" @change="onFileChange" accept=".txt,.docx,.pdf" required>
      <button type="submit">Submit</button>
    </form>
    <div>
      <h2>Response</h2>
      <button @click="loadSample">Populate with sample JSON</button>
    </div>
    <div v-if="rawJson">
      <h2>Raw JSON</h2>
      <pre class="raw-json">{{ rawJson }}</pre>
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
      activeIndex: null
    }
  },
  mounted() {
    this.fetchInternalText()
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
      try {
        const response = await axios.post('/api/process', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        this.rawJson = JSON.stringify(response.data, null, 2)
        // Assume response.data is the JSON from Gemini
        this.formattedData = response.data // if it's array
      } catch (error) {
        console.error(error)
      }
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