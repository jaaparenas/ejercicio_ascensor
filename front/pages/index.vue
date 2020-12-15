<template>
  <v-row justify="center" align="center">
    <v-col cols="12" sm="8" md="6">
      <v-container>
        <h1 class="text-center">Análisis del movimientos, ascensores del edificio Wayne</h1>
        <hr>
        <v-row>
          <v-col cols="12" class="text-center">
            Cantidad de ascensores
            <div class="cantidad-ascensores">
              <v-combobox
                v-model="cantidad_ascensores"
                :items="items_ascensores"
                outlined
                dense
              />
            </div>
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="12" class="text-center">
            <h2>Hora del análisis</h2>
            <v-time-picker v-model="hora_analisis"></v-time-picker>
          </v-col>
        </v-row>
        <v-row>
          <v-col cols="12">
            <v-btn color="primary" dark block @click="analisis()">
              Realizar el análisis
            </v-btn>
          </v-col>
        </v-row>
      </v-container>
    </v-col>
    <v-dialog v-model="dialog_error" width="500">
      <v-card>
        <v-card-title class="headline red">Error</v-card-title>
        <v-card-text>
          <br>
          Las horas para realizar el análisis deben estar entre:
          <ul>
            <li><strong>Hora inicial:</strong> {{ formatoHora(hora_inicial) }}</li>
            <li><strong>Hora final:</strong> {{ formatoHora(hora_final) }}</li>
          </ul>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="primary"
            text
            @click="dialog_error = false"
          >
            Ok
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialog_resultado" width="800">
      <v-card>
        <v-card-title class="headline blue">Resultado del análisis</v-card-title>
        <v-card-text>
          <br>
          <ul>
            <li v-for="data in data_analisis " :key="data.id">
              <div class="titulo-analisis">
                <span>Ascensor número {{ data.id + 1 }}</span>
                <i><a href="javascript:void(0)" @click="analisis_detallado(data.id)"> análisis detallado)</a></i>
              </div>
              <div>Planta en la que está ubicado actualmente: {{ data.piso_actual }}</div>
              <div>Total de plantas recorridas en el análisis: {{ data.pisos_movidos }}</div>
            </li>
          </ul>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="primary"
            text
            @click="dialog_resultado = false"
          >
            Ok
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialog_historico" width="800">
      <v-card>
        <v-card-title class="headline blue">Histórico de movimientos</v-card-title>
        <v-card-text>
          <br>
          <ol class="lista-detallado">
            <li v-for="(detallado, index) in data_analisis_detallado " :key="index">
              En la hora <strong>{{ formatoHora(detallado.hora_movimiento) }}</strong>, accionado por la secuencia
              <strong>{{ detallado.secuencia_movimiento }}</strong>,
              el ascensor se encontraba en la planta <strong>{{ detallado.piso_actual }}</strong> y fue llamado desde la planta
              <strong>{{ detallado.piso_llamado }}</strong>
              para ir hasta la planta <strong>{{ detallado.piso_destino }}</strong> desplanzandose por
              <strong>{{ detallado.pisos_movidos }}</strong> plantas acumulando con
              esto un total de <strong>{{ detallado.total_movidos }}</strong> plantas desplazadas.
            </li>
          </ol>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="primary"
            text
            @click="dialog_historico = false"
          >
            Ok
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-row>
</template>

<script>
export default {
  data () {
    return {
      data_analisis: {},
      data_analisis_detallado: {},
      dialog_error: false,
      dialog_resultado: false,
      dialog_historico: false,
      hora_analisis: '19:00',
      cantidad_ascensores: 3,
      items_ascensores: [3,4,5],
      hora_inicial: '',
      hora_final: ''
    }
  },
  components: {

  },
  methods: {
    analisis () {
      this.$axios.$get('/' + this.hora_analisis.replace(':', '-') + '/' + this.cantidad_ascensores).then((response) => {
        if (response.status === 'error') {
          this.dialog_error = true
          this.hora_inicial = response.data[0]
          this.hora_final = response.data[1]
        } else {
          this.dialog_resultado = true
          this.data_analisis = response.data
        }
      }).catch((error) => {
        console.log(error)
      })
    },
    analisis_detallado (id) {
      this.data_analisis_detallado = this.data_analisis.find(x => x.id == id).historico
      this.dialog_historico = true
    },
    formatoHora(_hora){
      let hora = _hora.toString().split(".")
      let part1 = (typeof hora[0] != "undefined") ? ("00" + hora[0]) : "00"
      let part2 = (typeof hora[1] != "undefined") ? (hora[1] + "00") : "00"
      return part1.substring(part1.length-2) + ":" + part2.substring(0,2)
    }
  }
}
</script>

<style scoped>
  .cantidad-ascensores {
    width: 80px;
    margin: auto;
  }
  .titulo-analisis span {
    font-size: 25px;
    font-weight: bold;
  }
  .titulo-analisis i {
    float: right;
  }
  .lista-detallado strong {
    color: #FFFFFF
  }
</style>